<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\ContactLabel;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

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

    $contact_label = new ContactLabel(['contact_id' => $contact->id, 'label_id' => 1]);

    $contact_label->save();

    $label = Label::factory()->create([
        'labelable_id' => $this->test_character->character_id,
        'labelable_type' => CharacterInfo::class,
        'label_id' => 1,
    ]);

    $this->assertNotNull(ContactLabel::first()->label_name);
});

test('contact has character affiliation', function () {
    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contact_id' => $this->test_character->character_id,
        'contact_type' => 'character',
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]));

    expect($contact->affiliations instanceof CharacterAffiliation)->toBeTrue();
    expect($contact->affiliations->character_id)->toEqual($this->test_character->character_id);
});

test('contact has corporation affiliation', function () {
    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contact_id' => $this->test_character->corporation->corporation_id,
        'contact_type' => 'corporation',
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]));

    expect($contact->affiliations instanceof CharacterAffiliation)->toBeTrue();
    expect($contact->affiliations->corporation_id)->toEqual($this->test_character->corporation->corporation_id);
});

test('contact has alliance affiliation', function () {
    $contact = Event::fakeFor(fn () => Contact::factory()->create([
        'contact_id' => $this->test_character->corporation->alliance_id,
        'contact_type' => 'alliance',
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]));

    expect($contact->affiliations instanceof CharacterAffiliation)->toBeTrue();
    expect($contact->affiliations->alliance_id)->toEqual($this->test_character->corporation->alliance_id);
});
