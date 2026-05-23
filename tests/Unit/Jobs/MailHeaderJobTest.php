<?php

use Seatplus\EsiClient\EsiClient;
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
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new MailHeaderJob(1);
    $job->executeJob($esi);

    expect(Mail::all())->toHaveCount(0);
});
