<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Event::fake();
});

test('run character contact label', function () {
    $mock_data = buildContactLabelMockEsiData();

    expect(Label::all())->toHaveCount(0);

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_contacts.v1'])->save();

    $job = new CharacterContactLabelJob(testCharacter()->character_id);

    $job->handle();

    expect(Label::all())->not()->toHaveCount(0);
});

test('run corporation contact label', function () {
    $mock_data = buildContactLabelMockEsiData();

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.read_contacts.v1'])->save();

    expect(Label::all())->toHaveCount(0);

    (new CorporationContactLabelJob(testCharacter()->corporation->corporation_id, testCharacter()->character_id))->handle();

    expect(Label::all())->not()->toHaveCount(0);
});

test('run alliance contact label', function () {
    $mock_data = buildContactLabelMockEsiData();

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-alliances.read_contacts.v1'])->save();

    expect(Label::all())->toHaveCount(0);

    (new AllianceContactLabelJob(testCharacter()->corporation->alliance_id, testCharacter()->character_id))->handle();

    expect(Label::all())->not()->toHaveCount(0);
});

// Helpers
function buildContactLabelMockEsiData()
{
    $mock_data = Label::factory()->count(5)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
