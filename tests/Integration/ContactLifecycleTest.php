<?php


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


uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

test('run character contact', function () {
    $mock_data = buildContactLifecycleMockEsiData();

    $job = new CharacterContactJob($this->job_container);

    dispatch_now($job);

    //assertContact($mock_data, $this->test_character->character_id);

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->character_id,
            'contact_id' => $data->contact_id,
        ]);
    }
});

test('run corporation contact', function () {
    $mock_data = buildContactLifecycleMockEsiData();

    $job = new CorporationContactJob($this->job_container);

    dispatch_now($job);

    //assertContact($mock_data, $this->test_character->corporation->corporation_id);

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->corporation_id,
            'contact_id' => $data->contact_id,
        ]);
    }
});

test('run alliance contact', function () {
    $mock_data = buildContactLifecycleMockEsiData();

    $job = new AllianceContactJob($this->job_container);

    dispatch_now($job);

    //assertContact($mock_data, $this->test_character->corporation->alliance_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->alliance_id,
            'contact_id' => $data->contact_id,
        ]);
    }
});

it('has labels', function () {
    $mock_data = Contact::factory()->withLabels()->make();

    $mock_data = mockRetrieveEsiDataAction([$mock_data->toArray()]);

    $job = new CharacterContactJob($this->job_container);

    expect($this->test_character->contacts)->toHaveCount(0);

    dispatch_now($job);

    //assertContact(collect($mock_data), $this->test_character->character_id);
    foreach (collect($mock_data) as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->character_id,
            'contact_id' => $data->contact_id,
        ]);
    }

    expect($this->test_character->refresh()->contacts)->toHaveCount(1);

    $contact = $this->test_character->refresh()->contacts->first();

    expect($contact->labels)->toHaveCount(3);
});

test('contact of type character dispatches affiliation job', function () {
    Queue::assertNothingPushed();

    $contact = Contact::factory()->create([
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
        'contact_type' => 'character',
    ]);

    Queue::assertPushedOn('high', CharacterAffiliationJob::class);
});

// Helpers
function buildContactLifecycleMockEsiData()
{
    $mock_data = Contact::factory()->count(5)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
