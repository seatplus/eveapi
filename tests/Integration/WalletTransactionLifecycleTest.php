<?php


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletTransactionByDivisionJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\Facade\RetrieveEsiData;

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

test('run wallet transaction action', function (bool $is_corporation = false) {

    $wallet_transactionable_id = $is_corporation ? testCharacter()->corporation->corporation_id : testCharacter()->character_id;

    Queue::assertNothingPushed();
    $mock_data = Event::fakeFor(fn () => WalletTransaction::factory()->count(5)->make([
        'wallet_transactionable_id' => $wallet_transactionable_id,
        'wallet_transactionable_type' => $is_corporation ? CorporationInfo::class : CharacterInfo::class,
        'division' => $is_corporation ? 1 : null,
    ]));
    Queue::assertNothingPushed();

    runWalletTransactionJobWithMockData($mock_data->toArray());

    //assertWalletTransaction($mock_data, $this->test_character->character_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_transactionable_id' => $wallet_transactionable_id,
            'transaction_id' => $data->transaction_id,
        ]);
    }
})->with([
    'character' => [false],
    'corporation' => [true],
]);

test('it dispatches follow up jobs', function (string $job_class, array $configuration, bool $should_dispatch) {


    $mock_data = WalletTransaction::factory()->make([
        'wallet_transactionable_id' => testCharacter()->character_id,
        'wallet_transactionable_type' => CharacterInfo::class,
        ...$configuration
    ]);

    runWalletTransactionJobWithMockData([$mock_data]);

    if ($should_dispatch) {
        Queue::assertPushedOn('high', $job_class);
    } else {
        Queue::assertNotPushed($job_class);
    }

})->with([
    'dispatching location job' => fn() => [ResolveLocationJob::class, [], true],
    'dispatching type job' => fn() => [ResolveUniverseTypeByIdJob::class, [], true],
    'not dispatching location job' => fn() => [
        ResolveLocationJob::class,
        ['location_id' => Location::factory()->create()->location_id],
        false
    ],
    'not dispatching type job' => fn() => [
        ResolveUniverseTypeByIdJob::class,
        ['type_id' => Type::factory()->create()->type_id],
        false
    ],
]);

function runWalletTransactionJobWithMockData(array $mock_data)
{

    updateRefreshTokenScopes(testCharacter()->refresh_token, ['esi-wallet.read_character_wallet.v1'])->save();

    $division_id = Arr::get($mock_data, '0.division', null);
    // If division is set, we are dealing with a corporation wallet transaction
    $is_corporation = ! is_null($division_id);

    if($is_corporation) {
        updateRefreshTokenScopes(testCharacter()->refresh_token, ['esi-wallet.read_corporation_wallets.v1'])->save();
        updateCharacterRoles(['Director']);
    }

    $response = new EsiResponse(json_encode($mock_data), [], 'now', 200);
    $response2 = new EsiResponse(json_encode([]), [], 'now', 200);

    RetrieveEsiData::shouldReceive('execute')->andReturns($response, $response2);

    $wallet_transactionable_id = Arr::get($mock_data, '0.wallet_transactionable_id');

    match ($is_corporation) {
        true => (new CorporationWalletTransactionByDivisionJob($wallet_transactionable_id, $division_id))->handle(),
        false => (new CharacterWalletTransactionJob($wallet_transactionable_id))->handle(),
    };
}
