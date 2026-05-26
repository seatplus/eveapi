<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\RefreshToken;

it('returns early if cache is hit', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $mail = Mail::factory()->create();
    $job = new MailBodyJob(testCharacter()->character_id, $mail->id);
    $job->executeJob($esi);

    expect(Mail::all())->toHaveCount(1)
        ->and(Mail::first()->body)->toBeNull();
});

it('returns the refresh token', function () {
    $token = RefreshToken::factory()->create();

    $job = new MailBodyJob($token->character_id, 99);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});
