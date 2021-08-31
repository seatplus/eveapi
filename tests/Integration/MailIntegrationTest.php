<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

it('runs mail header job', function () {
    $this->assertCount(0, Mail::all());

    buildHeaderMockEsiData();

    (new MailHeaderJob($this->job_container))->handle();

    $this->assertCount(5, Mail::all());
    $this->assertCount(15, MailRecipients::all());
    $this->assertInstanceOf(MailRecipients::class, Mail::first()->recipients->first());

    Queue::assertPushed(MailBodyJob::class);
});

it('runs mail body job', function () {
    $mail = Mail::factory()->create();

    buildBodyMockEsiData();

    $this->assertNull($mail->body);

    (new MailBodyJob($this->job_container, $mail->id))->handle();

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
        'character_id' => $this->test_character->character_id,
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

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}

function buildBodyMockEsiData()
{
    $this->mockRetrieveEsiDataAction([
        'body' => 'some elaborate long text body',
    ]);
}
