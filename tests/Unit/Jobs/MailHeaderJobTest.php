<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Models\Mail\Mail;

it('has tags', function () {
    $job = new MailHeaderJob(1);

    expect($job->tags())->toHaveCount(3)
        ->and($job->tags())->toContain('mail')
        ->and($job->tags())->toContain('header')
        ->and($job->tags())->toContain('character_id:1');
});

it('returns early if response is cached', function () {
    $response = mock(EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(MailHeaderJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(Mail::all())->toHaveCount(0);
});
