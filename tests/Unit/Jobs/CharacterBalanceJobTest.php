<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

it('returns correct tags array for character balance job', function () {
    $job = new CharacterBalanceJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'character',
        'character_id:12345',
        'wallet',
        'balance',
    ]);
});

it('does not upsert balances when response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterBalanceJob(12345);
    $job->executeJob($esi);

    expect(Balance::count())->toBe(0);
});
