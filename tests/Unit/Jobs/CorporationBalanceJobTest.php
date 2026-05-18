<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

it('returns correct tags array', function () {
    $job = new CorporationBalanceJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'corporation',
        'corporation_id:12345',
        'balances',
    ]);
});

it('does not upsert balances when response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('wallet->getCorporationsCorporationIdWallets')->andReturn(makeEsiResult([], isCachedLoad: true));

    $job = mock(CorporationBalanceJob::class)->makePartial();
    $job->executeJob($esi);

    expect(Balance::count())->toBe(0);
});
