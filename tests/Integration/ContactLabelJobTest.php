<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;

beforeEach(function () {
    Event::fake();
});

test('run character contact label', function () {
    $mock_data = Label::factory()->count(5)->make();

    mockEsiClient(
        'contacts->getCharactersCharacterIdContactsLabels',
        makeEsiResult(array_map(fn ($l) => (object) $l, $mock_data->toArray()))
    );

    expect(Label::all())->toHaveCount(0);

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_contacts.v1'])->save();

    runJob(new CharacterContactLabelJob(testCharacter()->character_id));

    expect(Label::all())->not()->toHaveCount(0);
});

test('run corporation contact label', function () {
    $mock_data = Label::factory()->count(5)->make();

    mockEsiClient(
        'contacts->getCorporationsCorporationIdContactsLabels',
        makeEsiResult(array_map(fn ($l) => (object) $l, $mock_data->toArray()))
    );

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.read_contacts.v1'])->save();

    expect(Label::all())->toHaveCount(0);

    runJob(new CorporationContactLabelJob(testCharacter()->corporation->corporation_id, testCharacter()->character_id));

    expect(Label::all())->not()->toHaveCount(0);
});

test('run alliance contact label', function () {
    $mock_data = Label::factory()->count(5)->make();

    mockEsiClient(
        'contacts->getAlliancesAllianceIdContactsLabels',
        makeEsiResult(array_map(fn ($l) => (object) $l, $mock_data->toArray()))
    );

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-alliances.read_contacts.v1'])->save();

    expect(Label::all())->toHaveCount(0);

    runJob(new AllianceContactLabelJob(testCharacter()->corporation->alliance_id, testCharacter()->character_id));

    expect(Label::all())->not()->toHaveCount(0);
});
