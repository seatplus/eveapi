<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Models\Mail\Mail;

it('returns early if cache is hit', function () {

    $response = mock(EsiResponse::class, function ($mock) {
        $mock->shouldReceive('isCachedLoad')->andReturn(true);
    });

    $job = mock(MailBodyJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(Mail::all())->toHaveCount(0);
});
