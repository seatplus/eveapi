<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletTransactionByDivisionJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

beforeEach(function () {
    Queue::fake();
});

test('run wallet transaction action', function (bool $isCorporation = false) {
    $walletTransactionableId = $isCorporation ? testCharacter()->corporation->corporation_id : testCharacter()->character_id;

    Queue::assertNothingPushed();
    $mockData = Event::fakeFor(fn () => WalletTransaction::factory()->count(5)->make([
        'wallet_transactionable_id' => $walletTransactionableId,
        'wallet_transactionable_type' => $isCorporation ? CorporationInfo::class : CharacterInfo::class,
        'division' => $isCorporation ? 1 : null,
    ]));
    Queue::assertNothingPushed();

    runWalletTransactionJobWithMockData($mockData->toArray());

    foreach ($mockData as $data) {
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_transactionable_id' => $walletTransactionableId,
            'transaction_id' => $data->transaction_id,
        ]);
    }
})->with([
    'character' => [false],
    'corporation' => [true],
]);

test('it dispatches follow up jobs', function (string $jobClass, array $configuration, bool $shouldDispatch) {
    $mockData = WalletTransaction::factory()->make([
        'wallet_transactionable_id' => testCharacter()->character_id,
        'wallet_transactionable_type' => CharacterInfo::class,
        ...$configuration,
    ]);

    runWalletTransactionJobWithMockData([$mockData]);

    if ($shouldDispatch) {
        Queue::assertPushedOn('high', $jobClass);
    } else {
        Queue::assertNotPushed($jobClass);
    }
})->with([
    'dispatching location job' => fn () => [ResolveLocationJob::class, [], true],
    'dispatching type job' => fn () => [ResolveUniverseTypeByIdJob::class, [], true],
    'not dispatching location job' => fn () => [
        ResolveLocationJob::class,
        ['location_id' => Location::factory()->create()->location_id],
        false,
    ],
    'not dispatching type job' => fn () => [
        ResolveUniverseTypeByIdJob::class,
        ['type_id' => Type::factory()->create()->type_id],
        false,
    ],
]);

function runWalletTransactionJobWithMockData(array $mockData): void
{
    $divisionId = Arr::get($mockData, '0.division', null);
    $isCorporation = ! is_null($divisionId);

    $walletTransactionableId = Arr::get($mockData, '0.wallet_transactionable_id');
    $items = array_map(fn ($t) => (object) (is_array($t) ? $t : $t->toArray()), $mockData);

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')
        ->andReturn(makeEsiRawResponse(makeEsiResult($items)), makeEsiRawResponse(makeEsiResult([])));

    if ($isCorporation) {
        updateRefreshTokenScopes(testCharacter()->refreshToken, ['esi-wallet.read_corporation_wallets.v1'])->save();
        updateCharacterRoles(['Director']);

        $job = new CorporationWalletTransactionByDivisionJob($walletTransactionableId, $divisionId);
        $job->executeJob($esi);

        return;
    }

    $job = new CharacterWalletTransactionJob($walletTransactionableId);
    $job->executeJob($esi);
}
