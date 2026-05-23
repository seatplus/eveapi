<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Models\Mail\Mail;

it('returns early if cache is hit', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $mail = Mail::factory()->create();
    $job = new MailBodyJob(testCharacter()->character_id, $mail->id);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(Mail::all())->toHaveCount(1)
        ->and(Mail::first()->body)->toBeNull();
});
