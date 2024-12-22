<?php

it('has tags', function () {
    $job = new \Seatplus\Eveapi\Jobs\Mail\MailHeaderJob(1);

    expect($job->tags())->toHaveCount(3)
        ->and($job->tags())->toContain('mail')
        ->and($job->tags())->toContain('header')
        ->and($job->tags())->toContain('character_id:1');
});

it('returns early if response is cached', function () {
    $response = mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(\Seatplus\Eveapi\Jobs\Mail\MailHeaderJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(\Seatplus\Eveapi\Models\Mail\Mail::all())->toHaveCount(0);
});
