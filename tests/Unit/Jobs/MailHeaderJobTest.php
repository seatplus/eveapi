<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\RefreshToken;

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

it('returns the refresh token', function () {
    $token = RefreshToken::factory()->create();

    $job = new MailHeaderJob($token->character_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});
