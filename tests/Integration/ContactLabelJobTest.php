<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CharacterContactLabelJob;
use Seatplus\Eveapi\Jobs\Contacts\CorporationContactLabelJob;
use Seatplus\Eveapi\Models\Contacts\Label;

beforeEach(function () {
    Event::fake();
});

test('run character contact label', function () {
    $mockData = Label::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($l) => (object) $l, $mockData->toArray())));

    expect(Label::all())->toHaveCount(0);

    $job = new CharacterContactLabelJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Label::all())->not()->toHaveCount(0);
});

test('run corporation contact label', function () {
    $mockData = Label::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($l) => (object) $l, $mockData->toArray())));

    expect(Label::all())->toHaveCount(0);

    $job = new CorporationContactLabelJob(testCharacter()->corporation->corporation_id, testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Label::all())->not()->toHaveCount(0);
});

test('run alliance contact label', function () {
    $mockData = Label::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($l) => (object) $l, $mockData->toArray())));

    expect(Label::all())->toHaveCount(0);

    $job = new AllianceContactLabelJob(testCharacter()->corporation->alliance_id, testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Label::all())->not()->toHaveCount(0);
});
