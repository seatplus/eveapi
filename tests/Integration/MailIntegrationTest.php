<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;

beforeEach(function () {
    Queue::fake();
});

it('runs mail header job', function () {
    expect(Mail::all())->toHaveCount(0);

    $mockedMails = Event::fakeFor(fn () => Mail::factory()->count(5)->make());

    $mockData = $mockedMails->map(fn ($mail) => (object) [
        'mail_id' => data_get($mail, 'id'),
        'subject' => data_get($mail, 'subject'),
        'from' => data_get($mail, 'from'),
        'timestamp' => data_get($mail, 'timestamp'),
        'is_read' => data_get($mail, 'is_read'),
        'character_id' => testCharacter()->character_id,
        'labels' => [1, 2, 3],
        'recipients' => [
            (object) ['recipient_id' => 123, 'recipient_type' => 'character'],
            (object) ['recipient_id' => 345, 'recipient_type' => 'corporation'],
            (object) ['recipient_id' => 678, 'recipient_type' => 'alliance'],
            (object) ['recipient_id' => 999, 'recipient_type' => 'mailing_list'],
        ],
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($mockData->values()->toArray()));

    $job = new MailHeaderJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Mail::all())->toHaveCount(5);
    expect(MailRecipients::all())->toHaveCount(25);
    expect(Mail::first()->recipients->first())->toBeInstanceOf(MailRecipients::class);

    Queue::assertPushed(MailBodyJob::class);
});

it('runs mail body job', function () {
    $mail = Mail::factory()->create();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) ['body' => 'some elaborate long text body']));

    expect($mail->body)->toBeNull();

    $job = new MailBodyJob(testCharacter()->character_id, $mail->id);
    $job->executeJob($esi);

    $this->assertNotNull($mail->refresh()->body);
});

it('adds MailBodyJob to batch if batched', function () {
    // Create 5 mails in DB without body
    $mails = Event::fakeFor(fn () => Mail::factory()->count(5)->create(['body' => null]));

    [$job, $batch] = new MailHeaderJob(testCharacter()->character_id)->withFakeBatch();

    // handleMailBody is public — call it directly with the mail collection mapped to expected shape
    $mailCollection = $mails->map(fn ($mail) => ['id' => $mail->id]);
    $job->handleMailBody(collect($mailCollection));

    expect($batch->added)
        ->toHaveCount(5)
        ->each->toBeInstanceOf(MailBodyJob::class);

    Queue::assertNothingPushed();
});
