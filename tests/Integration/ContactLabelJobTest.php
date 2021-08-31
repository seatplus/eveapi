<?php


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Event::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

test('run character contact label', function () {
    $mock_data = buildMockEsiData();

    $job = new CharacterContactLabelJob($this->job_container);

    dispatch_now($job);

    assertContactLabel($mock_data, $this->test_character->character_id);
});

test('run corporation contact label', function () {
    $mock_data = buildMockEsiData();

    $job = new CorporationContactLabelJob($this->job_container);

    dispatch_now($job);

    assertContactLabel($mock_data, $this->test_character->corporation->corporation_id);
});

test('run alliance contact label', function () {
    $mock_data = buildMockEsiData();

    $job = new AllianceContactLabelJob($this->job_container);

    dispatch_now($job);

    assertContactLabel($mock_data, $this->test_character->corporation->alliance_id);
});

// Helpers
function buildMockEsiData()
{
    $mock_data = Label::factory()->count(5)->make();

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}

function assertContactLabel(Collection $mock_data, int $labelable_id)
{
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('labels', [
            'labelable_id' => (string) $labelable_id,
            'label_id' => $data->label_id,
        ]);
    }
}
