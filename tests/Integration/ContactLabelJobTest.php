<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\ContactLabel;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ContactLabelJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    public JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        // Prevent any auto dispatching of jobs
        Event::fake();

        $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
    }

    /** @test */
    public function run_character_contact_label()
    {
        $mock_data = $this->buildMockEsiData();

        $job = new CharacterContactLabelJob($this->job_container);

        dispatch_now($job);

        $this->assertContactLabel($mock_data, $this->test_character->character_id);
    }

    /** @test */
    public function run_corporation_contact_label()
    {

        $mock_data = $this->buildMockEsiData();

        $job = new CorporationContactLabelJob($this->job_container);

        dispatch_now($job);

        $this->assertContactLabel($mock_data, $this->test_character->corporation->corporation_id);
    }

    /** @test */
    public function run_alliance_contact_label()
    {

        $mock_data = $this->buildMockEsiData();

        $job = new AllianceContactLabelJob($this->job_container);

        dispatch_now($job);

        $this->assertContactLabel($mock_data, $this->test_character->corporation->alliance_id);
    }

    private function buildMockEsiData()
    {

        $mock_data = Label::factory()->count(5)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function assertContactLabel(Collection $mock_data, int $labelable_id)
    {
        foreach ($mock_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('labels', [
                'labelable_id' => (string) $labelable_id,
                'label_id' => $data->label_id
            ]);
    }
}
