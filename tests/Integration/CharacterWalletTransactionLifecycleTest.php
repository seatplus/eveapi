<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

uses(TestCase::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

test('run wallet transaction action', function () {
    Queue::assertNothingPushed();
    $mock_data = Event::fakeFor(fn () => WalletTransaction::factory()->count(5)->make());
    Queue::assertNothingPushed();

    $response = new EsiResponse(json_encode($mock_data), [], 'now', 200);
    $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

    RetrieveEsiData::shouldReceive('execute')
        ->andReturns([$response, $response2]);

    $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
    $mock = Mockery::mock(CharacterWalletTransactionJob::class)->makePartial();

    $mock->shouldReceive('getCharacterId')
        ->andReturn($this->test_character->character_id);

    $mock->shouldReceive('retrieve')
        ->once()
        ->ordered()
        ->andReturn($response);

    $mock->shouldReceive('retrieve')
        ->once()
        ->ordered()
        ->andReturn($response2);

    $mock->handle();

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

// Helpers
/*function buildWalletTransactionMockEsiData()
{
    $mock_data = Event::fakeFor(fn () => WalletTransaction::factory()->count(5)->make());

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}*/

/*function mockRetrieveEsiDataAction(array $body) : void
{
    $data = json_encode($body);

    $response = new EsiResponse($data, [], 'now', 200);
    $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

    //$mock = Mockery::namedMock( CharacterWalletTransactionAction::class,  RetrieveFromEsiInterface::class)->makePartial();
    $mock = Mockery::mock('overload:' . RetrieveEsiData::class);

    $mock->shouldReceive('execute')
        ->once()
        ->ordered()
        ->andReturn($response);

    $mock->shouldReceive('execute')
        ->once()
        ->ordered()
        ->andReturn($response2);
}*/
