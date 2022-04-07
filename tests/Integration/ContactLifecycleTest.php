<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
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

    $job->handle();

    $cached_ids = \Seatplus\Eveapi\Services\Jobs\CharacterAffiliationService::make()->retrieve();

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->character_id,
            'contact_id' => $data->contact_id,
        ]);

        if($data->contact_type === 'character') {
            expect(in_array($data->contact_id, $cached_ids->toArray()))->toBeTrue();
        }

    }

});

test('run corporation contact', function () {
    $mock_data = buildContactLifecycleMockEsiData();

    $job = new CorporationContactJob($this->job_container);

    $job->handle();

    $cached_ids = \Seatplus\Eveapi\Services\Jobs\CharacterAffiliationService::make()->retrieve();

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->corporation_id,
            'contact_id' => $data->contact_id,
        ]);

        if($data->contact_type === 'character') {
            expect(in_array($data->contact_id, $cached_ids->toArray()))->toBeTrue();
        }
    }
});

test('run alliance contact', function () {
    $mock_data = buildContactLifecycleMockEsiData();

    $job = new AllianceContactJob($this->job_container);

    $job->handle();

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

    $job->handle();


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

// Helpers
function buildContactLifecycleMockEsiData()
{
    $mock_data = Contact::factory()->count(5)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
