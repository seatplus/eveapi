<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ContactLifecycleTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    public JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        // Prevent any auto dispatching of jobs
        Queue::fake();

        $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
    }

    /** @test */
    public function run_character_contact()
    {
        $mock_data = $this->buildMockEsiData();

        $job = new CharacterContactJob($this->job_container);

        dispatch_now($job);

        $this->assertContact($mock_data, $this->test_character->character_id);
    }

    /** @test */
    public function run_corporation_contact()
    {
        $mock_data = $this->buildMockEsiData();

        $job = new CorporationContactJob($this->job_container);

        dispatch_now($job);

        $this->assertContact($mock_data, $this->test_character->corporation->corporation_id);
    }

    /** @test */
    public function run_alliance_contact()
    {
        $mock_data = $this->buildMockEsiData();

        $job = new AllianceContactJob($this->job_container);

        dispatch_now($job);

        $this->assertContact($mock_data, $this->test_character->corporation->alliance_id);
    }

    /** @test  */
    public function it_has_labels()
    {
        $mock_data = Contact::factory()->withLabels()->make();

        $mock_data = $this->mockRetrieveEsiDataAction([$mock_data->toArray()]);

        $job = new CharacterContactJob($this->job_container);

        $this->assertCount(0, $this->test_character->contacts);

        dispatch_now($job);

        $this->assertContact(collect($mock_data), $this->test_character->character_id);

        $this->assertCount(1, $this->test_character->refresh()->contacts);

        $contact = $this->test_character->refresh()->contacts->first();

        $this->assertCount(3, $contact->labels);
    }

    /** @test */
    public function contact_of_type_character_dispatches_affiliation_job()
    {

        Queue::assertNothingPushed();

        $contact = Contact::factory()->create([
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class,
            'contact_type' => 'character'
        ]);

        Queue::assertPushedOn('high', CharacterAffiliationJob::class);

    }

    private function buildMockEsiData()
    {

        $mock_data = Contact::factory()->count(5)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function assertContact(Collection $mock_data, int $contactable_id)
    {
        foreach ($mock_data as $data)
            //Assert that character asset created
            $this->assertDatabaseHas('contacts', [
                'contactable_id' => $contactable_id,
                'contact_id' => $data->contact_id
            ]);
    }
}
