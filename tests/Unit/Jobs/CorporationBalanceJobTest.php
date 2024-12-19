<?php

use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;

it('returns correct tags array', function () {
    $job = new CorporationBalanceJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'corporation',
        'corporation_id: 12345',
        'balances',
    ]);
});

it('does not upsert balances when response is cached', function () {


    $job = mock(CorporationBalanceJob::class, function (MockInterface $mock) {

        $response = mock(EsiResponse::class, function (MockInterface $mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(Balance::count())->toBe(0);
});
