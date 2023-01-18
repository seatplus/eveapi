<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

test('run wallet transaction action', function () {
    Queue::assertNothingPushed();
    $mock_data = Event::fakeFor(fn () => WalletTransaction::factory()->count(5)->make());
    Queue::assertNothingPushed();

    $response = new EsiResponse(json_encode($mock_data), [], 'now', 200);
    $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

    RetrieveEsiData::shouldReceive('execute')->andReturns($response, $response2);

    (new CharacterWalletTransactionJob($this->test_character->character_id))->handle();

    //assertWalletTransaction($mock_data, $this->test_character->character_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_transactionable_id' => $this->test_character->character_id,
            'transaction_id' => $data->transaction_id,
        ]);
    }
});

test('creation of character wallet transaction dispatches jobs', function () {
    $wallet_transaction = WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
    ]);

    Queue::assertPushedOn('high', ResolveLocationJob::class);
    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
});

test('creation of corporation wallet transaction dispatches jobs', function () {
    $wallet_transaction = WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->corporation->corporation_id,
        'wallet_transactionable_type' => CorporationInfo::class,
    ]);

    Queue::assertPushedOn('high', ResolveLocationJob::class);
    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
});

