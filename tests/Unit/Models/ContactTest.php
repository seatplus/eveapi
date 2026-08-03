<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\ContactLabel;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

beforeEach(function () {
    $alliance = AllianceInfo::factory()->create();

    $testCorporation = $this->test_character->corporation;
    $testCorporation->alliance_id = $alliance->alliance_id;
    $testCorporation->save();

    $this->test_character = $this->test_character->refresh();
});

test('character has contact test', function () {
    expect($this->test_character->contacts)->toHaveCount(0);

    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]));

    expect($contact->contactable)->toBeInstanceOf(CharacterInfo::class);

    $character = $this->test_character->refresh();
    expect($character->contacts)->toHaveCount(1);
    expect($character->contacts->first())->toBeInstanceOf(Contact::class);
});

test('corporation has contact test', function () {
    expect($this->test_character->corporation->contacts)->toHaveCount(0);

    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contactable_id' => $this->test_character->corporation->corporation_id,
        'contactable_type' => CorporationInfo::class,
    ]));

    expect($contact->contactable)->toBeInstanceOf(CorporationInfo::class);

    $corporation = $this->test_character->corporation->refresh();
    expect($corporation->contacts)->toHaveCount(1);
    expect($corporation->contacts->first())->toBeInstanceOf(Contact::class);
});

test('alliance has contact test', function () {
    expect($this->test_character->alliance->contacts)->toHaveCount(0);

    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contactable_id' => $this->test_character->alliance->alliance_id,
        'contactable_type' => AllianceInfo::class,
    ]));

    expect($contact->contactable)->toBeInstanceOf(AllianceInfo::class);

    $alliance = $this->test_character->alliance->refresh();
    expect($alliance->contacts)->toHaveCount(1);
    expect($alliance->contacts->first())->toBeInstanceOf(Contact::class);
});

test('contact has label', function () {
    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]));

    expect($contact->labels)->toHaveCount(0);

    $contactLabel = new ContactLabel(['contact_id' => $contact->id, 'label_id' => 1]);

    $contactLabel->save();

    $label = Label::factory()->create([
        'labelable_id' => $this->test_character->character_id,
        'labelable_type' => CharacterInfo::class,
        'label_id' => 1,
    ]);

    $this->assertNotNull(ContactLabel::first()->label_name);
});

it('has has affiliation relationship', function (string $contactType) {
    $affiliation = CharacterAffiliation::factory()->create([
        'alliance_id' => faker()->numberBetween(99000000, 100000000),
        'faction_id' => faker()->numberBetween(500000, 1000000),
    ]);

    $contactId = match ($contactType) {
        'character' => $affiliation->character_id,
        'corporation' => $affiliation->corporation_id,
        'alliance' => $affiliation->alliance_id,
        'faction' => $affiliation->faction_id,
        default => throw new UnhandledMatchError,
    };

    Contact::factory()->create([
        'contact_id' => $contactId,
        'contact_type' => $contactType,
        'standing' => 10.0,
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]);

    expect(Contact::all())->toHaveCount(1)
        ->first()->affiliation->toBeInstanceOf(CharacterAffiliation::class);
})->with([
    'character', 'corporation', 'alliance', 'faction',
]);

it('has entity filter scope', function () {
    Contact::factory()->create([
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]);

    Contact::factory()->create([
        'contactable_id' => $this->test_character->corporation->corporation_id,
        'contactable_type' => CorporationInfo::class,
    ]);

    $contacts = Contact::query()
        ->entityFilter([$this->test_character->corporation->corporation_id])
        ->get();

    expect($contacts)->toHaveCount(1);
});
