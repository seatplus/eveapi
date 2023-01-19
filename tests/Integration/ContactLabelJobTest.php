<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Event::fake();
});

test('run character contact label', function () {
    $mock_data = buildContactLabelMockEsiData();

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_contacts.v1'])->save();

    $job = new CharacterContactLabelJob(testCharacter()->character_id);

    dispatch_sync($job);

    //assertContactLabel($mock_data, $this->test_character->character_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('labels', [
            'labelable_id' => (string) $this->test_character->character_id,
            'label_id' => $data->label_id,
        ]);
    }
});

test('run corporation contact label', function () {
    $mock_data = buildContactLabelMockEsiData();

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.read_contacts.v1'])->save();

    $job = new CorporationContactLabelJob(testCharacter()->corporation->corporation_id);

    dispatch_sync($job);

    //assertContactLabel($mock_data, $this->test_character->corporation->corporation_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('labels', [
            'labelable_id' => (string) $this->test_character->corporation->corporation_id,
            'label_id' => $data->label_id,
        ]);
    }
});

test('run alliance contact label', function () {
    $mock_data = buildContactLabelMockEsiData();

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-alliances.read_contacts.v1'])->save();

    $job = new AllianceContactLabelJob(testCharacter()->corporation->alliance_id);

    dispatch_sync($job);

    //assertContactLabel($mock_data, $this->test_character->corporation->alliance_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('labels', [
            'labelable_id' => (string) $this->test_character->corporation->alliance_id,
            'label_id' => $data->label_id,
        ]);
    }
});

// Helpers
function buildContactLabelMockEsiData()
{
    $mock_data = Label::factory()->count(5)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
