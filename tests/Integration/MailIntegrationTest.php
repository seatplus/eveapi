<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

it('runs mail header job', function () {
    expect(Mail::all())->toHaveCount(0);

    buildHeaderMockEsiData();

    (new MailHeaderJob(testCharacter()->character_id))->handle();

    expect(Mail::all())->toHaveCount(5);
    expect(MailRecipients::all())->toHaveCount(25);
    expect(Mail::first()->recipients->first())->toBeInstanceOf(MailRecipients::class);

    Queue::assertPushed(MailBodyJob::class);
});

it('runs mail body job', function () {
    $mail = Mail::factory()->create();

    buildBodyMockEsiData();

    expect($mail->body)->toBeNull();

    (new MailBodyJob(testCharacter()->character_id, $mail->id))->handle();

    $this->assertNotNull($mail->refresh()->body);
});

it('adds MailBodyJob to batch if batched', function () {

    $job = mock(MailHeaderJob::class, function (MockInterface $mock) {
        $mock->method = 'get';
        $mock->endpoint = '/characters/{character_id}/mail/';
        $mock->version = 'v1';
        $mock->required_scope = 'esi-mail.read_mail.v1';
        $mock->path_values = [
            'character_id' => testCharacter()->character_id,
        ];
        $mock->character_id = testCharacter()->character_id;

        $mocked_mails = Event::fakeFor(fn () => Mail::factory()->count(5)->make());
        $mock_data = $mocked_mails->map(fn ($mail) => [
            'mail_id' => data_get($mail, 'id'),
            'subject' => data_get($mail, 'subject'),
            'from' => data_get($mail, 'from'),
            'timestamp' => data_get($mail, 'timestamp'),
            'is_read' => data_get($mail, 'is_read'),
            'character_id' => testCharacter()->character_id,
            'labels' => [
                1, 2, 3,
            ],
        ]);
        $response = new EsiResponse(json_encode($mock_data->toArray()), [], 'now', 200);

        $mock->shouldReceive('retrieve')->andReturn($response);

        // make it batching
        $mock->shouldReceive('batching')->andReturnTrue();
        $mock->shouldReceive('batch->add')->times(5);

    })->makePartial();

    $job->executeJob();
});

// Helpers
function buildHeaderMockEsiData()
{
    Queue::assertNothingPushed();

    $mocked_mails = Event::fakeFor(fn () => Mail::factory()->count(5)->make());

    Queue::assertNothingPushed();

    $mock_data = $mocked_mails->map(fn ($mail) => [
        'mail_id' => data_get($mail, 'id'),
        'subject' => data_get($mail, 'subject'),
        'from' => data_get($mail, 'from'),
        'timestamp' => data_get($mail, 'timestamp'),
        'is_read' => data_get($mail, 'is_read'),
        'character_id' => testCharacter()->character_id,
        'labels' => [
            1, 2, 3,
        ],
        'recipients' => [
            [
                'recipient_id' => 123,
                'recipient_type' => 'character',
            ], [
                'recipient_id' => 345,
                'recipient_type' => 'corporation',
            ], [
                'recipient_id' => 678,
                'recipient_type' => 'alliance',
            ], [
                'recipient_id' => 999,
                'recipient_type' => 'mailing_list',
            ],
        ],
    ]);

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}

function buildBodyMockEsiData()
{
    mockRetrieveEsiDataAction([
        'body' => 'some elaborate long text body',
    ]);
}
