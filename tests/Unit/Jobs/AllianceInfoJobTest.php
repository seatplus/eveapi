<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;

it('returns early if batch is cancelled', function () {
    $job = mock(AllianceInfoJob::class, function ($mock) {
        $mock->shouldReceive('batching')->andReturn(true);
        $mock->shouldReceive('batch->cancelled')->once()->andReturn(true);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});

it('checks if the response is cached', function () {
    $job = mock(AllianceInfoJob::class, function ($mock) {
        $response = mock(EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->once()->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});
