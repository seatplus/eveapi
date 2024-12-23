<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;

it('returns early if cache is hit', function () {


    $response = mock(EsiResponse::class, function ($mock) {
        $mock->shouldReceive('isCachedLoad')->andReturn(true);
    });

    $job = mock(MailBodyJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(\Seatplus\Eveapi\Models\Mail\Mail::all())->toHaveCount(0);
});
