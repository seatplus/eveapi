<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;

it('checks if the response is cached', function () {
    $job = mock(CorporationHistoryJob::class, function ($mock) {
        $response = mock(EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(CorporationHistory::count())->toBe(0);
});

it('has tags', function () {
    $job = new CorporationHistoryJob($character_id = 1);

    expect($job->tags())->toBe([
        'character',
        'info',
        'character_id:'.$character_id,
        'corporationhistory',
    ]);
});
