<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

it('returns correct tags array for character balance job', function () {
    $job = new CharacterBalanceJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'character',
        'character_id: 12345',
        'wallet',
        'balance',
    ]);
});

it('does not upsert balances when response is cached', function () {
    $job = mock(CharacterBalanceJob::class, function ($mock) {
        $response = mock(EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(Balance::count())->toBe(0);
});
