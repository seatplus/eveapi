<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService;

beforeEach(function () {
    Queue::fake();
});

test('run character contact', function () {
    $mock_data = Contact::factory()->count(5)->make();

    mockEsiClient(
        'contacts->getCharactersCharacterIdContacts',
        makeEsiResult(array_map(fn ($c) => (object) $c, $mock_data->toArray()))
    );

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_contacts.v1'])->save();

    runJob(new CharacterContactJob(testCharacter()->character_id));

    $cached_ids = CacheCharacterAffiliationIdsService::make()->retrieve();

    foreach ($mock_data as $data) {
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->character_id,
            'contact_id' => $data->contact_id,
        ]);

        if ($data->contact_type === 'character') {
            expect(in_array($data->contact_id, $cached_ids->toArray()))->toBeTrue();
        }
    }
});

test('run corporation contact', function () {
    $mock_data = Contact::factory()->count(5)->make();

    mockEsiClient(
        'contacts->getCorporationsCorporationIdContacts',
        makeEsiResult(array_map(fn ($c) => (object) $c, $mock_data->toArray()))
    );

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-corporations.read_contacts.v1'])->save();

    runJob(new CorporationContactJob(testCharacter()->corporation->corporation_id, testCharacter()->character_id));

    $cached_ids = CacheCharacterAffiliationIdsService::make()->retrieve();

    foreach ($mock_data as $data) {
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->corporation_id,
            'contact_id' => $data->contact_id,
        ]);

        if ($data->contact_type === 'character') {
            expect(in_array($data->contact_id, $cached_ids->toArray()))->toBeTrue();
        }
    }
});

test('run alliance contact', function () {
    $mock_data = Contact::factory()->count(5)->make();

    mockEsiClient(
        'contacts->getAlliancesAllianceIdContacts',
        makeEsiResult(array_map(fn ($c) => (object) $c, $mock_data->toArray()))
    );

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-alliances.read_contacts.v1'])->save();

    runJob(new AllianceContactJob(testCharacter()->corporation->alliance_id, testCharacter()->character_id));

    foreach ($mock_data as $data) {
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->alliance_id,
            'contact_id' => $data->contact_id,
        ]);
    }
});

it('has labels', function () {
    $mock_data = Contact::factory()->withLabels()->make();

    mockEsiClient(
        'contacts->getCharactersCharacterIdContacts',
        makeEsiResult([(object) $mock_data->toArray()])
    );

    updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_contacts.v1'])->save();

    expect($this->test_character->contacts)->toHaveCount(0);

    runJob(new CharacterContactJob(testCharacter()->character_id));

    expect($this->test_character->refresh()->contacts)->toHaveCount(1);

    $contact = $this->test_character->refresh()->contacts->first();

    expect($contact->labels)->toHaveCount(3);
});
