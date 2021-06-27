<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Mail\MailBodyJob;
use Seatplus\Eveapi\Jobs\Mail\MailHeaderJob;
use Seatplus\Eveapi\Jobs\Mail\MailLabelJob;
use Seatplus\Eveapi\Models\Mail\MailMailLabel;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailLabel;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class MailIntegrationTest extends TestCase
{

    use MockRetrieveEsiDataAction;

    private JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        // Prevent any auto dispatching of jobs
        Queue::fake();

        $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

    }

    /** @test */
    public function itRunsMailHeaderJob()
    {
        $this->assertCount(0, Mail::all());

        $this->buildHeaderMockEsiData();

        (new MailHeaderJob($this->job_container))->handle();

        // Assert Labels
        $this->assertDatabaseCount('mail_mail_label',15);
        $this->assertCount(15, MailMailLabel::all());
        $this->assertTrue(MailMailLabel::first()->mail instanceof Mail);


        $this->assertCount(5, Mail::all());
        $this->assertCount(15, MailRecipients::all());

        Queue::assertPushed(MailBodyJob::class);
    }

    /** @test */
    public function itRunsMailBodyJob()
    {
        $mail = Mail::factory()->create();

        $this->buildBodyMockEsiData();

        $this->assertNull($mail->body);

        (new MailBodyJob($this->job_container, $mail->id))->handle();

        $this->assertNotNull($mail->refresh()->body);
    }

    /** @test */
    public function itRunsMailLabelJob()
    {

        $this->buildLabelMockEsiData();

        (new MailLabelJob($this->job_container))->handle();

        $this->assertCount(5, MailLabel::all());

        $mail = Mail::first();

        $this->assertCount(1, Mail::all());

        $mail_label = MailLabel::first();

        $this->assertCount(0, Mail::first()->labels);

        MailMailLabel::create([
            'mail_id' => $mail->id,
            'label_id' => $mail_label->label_id,
            'character_id' => $this->test_character->character_id
        ]);

        $this->assertDatabaseCount('mail_mail_label',1);
        $this->assertDatabaseHas('mail_mail_label', [
            'mail_id' => $mail->id,
            'label_id' => $mail_label->label_id
        ]);

        $this->assertTrue(MailMailLabel::first()->mail instanceof Mail);
        $this->assertTrue(MailMailLabel::first()->label instanceof MailLabel);

        $this->assertCount(1, MailLabel::first()->mails);
        $this->assertEquals(Mail::first()->id, MailLabel::first()->mails->first()->id);

        $this->assertCount(1, Mail::first()->labels);

    }


    private function buildHeaderMockEsiData()
    {

        Queue::assertNothingPushed();

        $mocked_mails = Event::fakeFor(fn() => Mail::factory()->count(5)->make());

        Queue::assertNothingPushed();

        $mock_data = $mocked_mails->map(fn($mail) => [
            'mail_id' => data_get($mail, 'id'),
            'subject' => data_get($mail, 'subject'),
            'from' => data_get($mail, 'from'),
            'timestamp' => data_get($mail, 'timestamp'),
            'is_read' => data_get($mail, 'is_read'),
            'character_id' => $this->test_character->character_id,
            'labels' => [
                1, 2, 3
            ],
            'recipients' => [
                [
                    'recipient_id' => 123,
                    'recipient_type' => 'character'
                ], [
                    'recipient_id' => 345,
                    'recipient_type' => 'corporation'
                ]
            ]
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function buildBodyMockEsiData()
    {

        $this->mockRetrieveEsiDataAction([
            'body' => 'some elaborate long text body'
        ]);
    }

    private function buildLabelMockEsiData()
    {

        $mail = Event::fakeFor(fn() => Mail::factory()->create());
        $mocked_labels = Event::fakeFor(fn() => MailLabel::factory()->count(5)->make([
            'mail_id' => $mail->id,
            'character_id' => $this->test_character->character_id
        ]));

        $mock_data = [
            'labels' => $mocked_labels->toArray(),
            'total_unread_count' => 4
        ];

        $this->mockRetrieveEsiDataAction($mock_data);

        return $mock_data;
    }

}