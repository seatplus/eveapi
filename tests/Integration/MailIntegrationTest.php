<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

it('runs mail header job', function () {
    expect(Mail::all())->toHaveCount(0);

    buildHeaderMockEsiData();

    (new MailHeaderJob(testCharacter()->character_id))->handle();

    expect(Mail::all())->toHaveCount(5);
    expect(MailRecipients::all())->toHaveCount(15);
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
