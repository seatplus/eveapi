<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactJob;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService;

beforeEach(function () {
    Queue::fake();
});

test('run character contact', function () {
    $mockData = Contact::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($c) => (object) $c, $mockData->toArray())));

    $job = new CharacterContactJob(testCharacter()->character_id);
    $job->executeJob($esi);

    $cachedIds = CacheCharacterAffiliationIdsService::make()->retrieve();

    foreach ($mockData as $data) {
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->character_id,
            'contact_id' => $data->contact_id,
        ]);

        if ($data->contact_type === 'character') {
            expect(in_array($data->contact_id, $cachedIds->toArray()))->toBeTrue();
        }
    }
});

test('run corporation contact', function () {
    $mockData = Contact::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($c) => (object) $c, $mockData->toArray())));

    $job = new CorporationContactJob(testCharacter()->corporation->corporation_id, testCharacter()->character_id);
    $job->executeJob($esi);

    $cachedIds = CacheCharacterAffiliationIdsService::make()->retrieve();

    foreach ($mockData as $data) {
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->corporation_id,
            'contact_id' => $data->contact_id,
        ]);

        if ($data->contact_type === 'character') {
            expect(in_array($data->contact_id, $cachedIds->toArray()))->toBeTrue();
        }
    }
});

test('run alliance contact', function () {
    $mockData = Contact::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($c) => (object) $c, $mockData->toArray())));

    $job = new AllianceContactJob(testCharacter()->corporation->alliance_id, testCharacter()->character_id);
    $job->executeJob($esi);

    foreach ($mockData as $data) {
        $this->assertDatabaseHas('contacts', [
            'contactable_id' => $this->test_character->corporation->alliance_id,
            'contact_id' => $data->contact_id,
        ]);
    }
});

it('has labels', function () {
    $mockData = Contact::factory()->withLabels()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) $mockData->toArray()]));

    expect($this->test_character->contacts)->toHaveCount(0);

    $job = new CharacterContactJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect($this->test_character->refresh()->contacts)->toHaveCount(1);

    $contact = $this->test_character->refresh()->contacts->first();

    expect($contact->labels)->toHaveCount(3);
});
