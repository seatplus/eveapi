<?php

it('returns early if batch is cancelled', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob::class, function ($mock) {
        $mock->shouldReceive('batching')->andReturn(true);
        $mock->shouldReceive('batch->cancelled')->once()->andReturn(true);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});

it('checks if the response is cached', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob::class, function ($mock) {
        $response = mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class, function ($mock) {
            $mock->shouldReceive('isCachedLoad')->once()->andReturn(true);
        });

        $mock->shouldReceive('retrieve')->andReturn($response);
    })->makePartial();

    $job->executeJob();

    expect(true)->toBeTrue();
});
