<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CorporationsCorporationIdWalletsDivisionJournalGetItem;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalByDivisionJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletTransactionByDivisionJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

it('runs the job', function () {
    Queue::fake();

    $mockData = Balance::factory()->withDivision()->count(7)->make([
        'balanceable_id' => testCharacter()->corporation->corporation_id,
        'balanceable_type' => CorporationInfo::class,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($b) => (object) $b, $mockData->toArray())));

    expect(Balance::all())->toHaveCount(0);

    Event::fake();

    $job = new CorporationBalanceJob(testCharacter()->corporation->corporation_id);
    $job->executeJob($esi);

    expect(Balance::all())->toHaveCount(7);
});

it('dispatches follow up jobs', function () {
    Queue::fake();

    $balances = Balance::factory()
        ->withDivision()
        ->count(7)
        ->make([
            'balanceable_id' => $this->test_character->corporation->corporation_id,
            'balanceable_type' => CorporationInfo::class,
        ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($b) => (object) $b, $balances->toArray())));

    $job = new CorporationBalanceJob(testCharacter()->corporation->corporation_id);
    $job->executeJob($esi);

    Queue::assertPushed(CorporationWalletJournalByDivisionJob::class);
    Queue::assertPushed(CorporationWalletTransactionByDivisionJob::class);
});

it('spawns a job for every wallet division', function () {
    Queue::fake();

    Event::fakeFor(fn () => Balance::factory()->withDivision()->count(7)->create([
        'balanceable_id' => $this->test_character->corporation->corporation_id,
        'balanceable_type' => CorporationInfo::class,
    ]));

    new CorporationWalletJournalJob(testCharacter()->corporation->corporation_id)->handle();

    expect(Balance::all())->toHaveCount(7);

    Queue::assertPushed(CorporationWalletJournalByDivisionJob::class);
});

it('creates wallet journal entries', function () {
    $corporation = $this->test_character->corporation;

    expect($corporation->walletJournals)->toHaveCount(0);

    $mockData = WalletJournal::factory()->count(7)->make([
        'wallet_journable_id' => testCharacter()->corporation->corporation_id,
        'wallet_journable_type' => CorporationInfo::class,
        'division' => 3,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($mockData->map(fn (WalletJournal $j) => CorporationsCorporationIdWalletsDivisionJournalGetItem::from(
        (object) $j->toArray()
    ))->toArray()));

    $job = new CorporationWalletJournalByDivisionJob(testCharacter()->corporation->corporation_id, $mockData->first()->division);
    $job->executeJob($esi);

    $corporation = $corporation->refresh();
    expect($corporation->walletJournals)->toHaveCount($mockData->count());
    expect($corporation->walletJournals->first())->toBeInstanceOf(WalletJournal::class);
});
