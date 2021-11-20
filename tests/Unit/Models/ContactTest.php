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

   $test_corporation = $this->test_character->corporation;
   $test_corporation->alliance_id = $alliance->alliance_id;
   $test_corporation->save();

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

    $contact_label = new ContactLabel(['contact_id' => $contact->id, 'label_id' => 1]);

    $contact_label->save();

    $label = Label::factory()->create([
        'labelable_id' => $this->test_character->character_id,
        'labelable_type' => CharacterInfo::class,
        'label_id' => 1,
    ]);

    $this->assertNotNull(ContactLabel::first()->label_name);
});


test('withStandings scope returns contact with corporation and alliance standing', function ($contactable_id, $contactable_type, $contact_type) {

    Event::fake();
    expect($contactable_id)->toBeNumeric();

    $affiliation = CharacterAffiliation::factory()->create([
        'alliance_id' => faker()->numberBetween(99000000, 100000000),
        'faction_id' => faker()->numberBetween(500000, 1000000),
    ]);

    // 1. Create contact of test_character
    Contact::factory()->create([
        'contact_id' => $affiliation->character_id,
        'contact_type' => 'character',
        'standing' => 10.0,
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]);

    expect(Contact::all())->toHaveCount(1);

    // 2. Create contact for contactable type with negative standing
    $contact_id = match ($contact_type) {
        'character' => $affiliation->character_id,
        'corporation' => $affiliation->corporation_id,
        'alliance' => $affiliation->alliance_id,
        'faction' => $affiliation->faction_id,
    };

    expect($contact_id)->toBeNumeric();

    Contact::factory()->create([
        'contact_id' => $contact_id,
        'contact_type' => $contact_type,
        'standing' => -5.0,
        'contactable_id' => $contactable_id,
        'contactable_type' => $contactable_type,
    ]);

    expect(Contact::all())->toHaveCount(2);

    // Then for the test user get the contact and see the standing
    $result = Contact::query()
        ->where('contactable_id', $this->test_character->character_id)
        ->withStandings($this->test_character->corporation->corporation_id, $this->test_character->alliance->alliance_id)
        ->get();

    expect($result)
        ->toHaveCount(1)
        ->first()->standing->toBe(10.0);

    match ($contactable_type) {
        CorporationInfo::class => expect($result)->first()->corporation_standing->toBe(-5.0),
        AllianceInfo::class => expect($result)->first()->alliance_standing->toBe(-5.0),
    };

})->with([
    [fn() => $this->test_character->corporation->corporation_id, CorporationInfo::class, 'character'],
    [fn() => $this->test_character->corporation->corporation_id, CorporationInfo::class, 'corporation'],
    [fn() => $this->test_character->corporation->corporation_id, CorporationInfo::class, 'alliance'],
    [fn() => $this->test_character->corporation->corporation_id, CorporationInfo::class, 'faction'],
    [fn() => $this->test_character->alliance->alliance_id, AllianceInfo::class, 'character'],
    [fn() => $this->test_character->alliance->alliance_id, AllianceInfo::class, 'corporation'],
    [fn() => $this->test_character->alliance->alliance_id, AllianceInfo::class, 'alliance'],
    [fn() => $this->test_character->alliance->alliance_id, AllianceInfo::class, 'faction'],
]);

it('returns highest hierarchical standing of multiple contact ', function ($contactable_id, $contactable_type) {

    Event::fake();

    $affiliation = CharacterAffiliation::factory()->create([
        'alliance_id' => faker()->numberBetween(99000000, 100000000),
        'faction_id' => faker()->numberBetween(500000, 1000000),
    ]);

    // 1. Create contact of test_character
    $contact = Contact::factory()->create([
        'contact_id' => $affiliation->character_id,
        'contact_type' => 'character',
        'standing' => 10.0,
        'contactable_id' => $this->test_character->character_id,
        'contactable_type' => CharacterInfo::class,
    ]);

    expect(Contact::all())->toHaveCount(1);

    // 2. Let's add that contact as character to contactable_type contacts
    Contact::factory()->create([
        'contact_id' => $affiliation->character_id,
        'contact_type' => 'character',
        'standing' => 5.0,
        'contactable_id' => $contactable_id,
        'contactable_type' => $contactable_type,
    ]);

    expect(Contact::all())->toHaveCount(2);
    expect(Contact::query()->where('contactable_type',CharacterInfo::class)->get())
        ->toHaveCount(1);
    expect(Contact::query()->where('contactable_type', $contactable_type)->get())
        ->toHaveCount(1);

    // 3. But the contactable_type does not like its corporation
    Contact::factory()->create([
        'contact_id' => $affiliation->corporation_id,
        'contact_type' => 'corporation',
        'standing' => -10.0,
        'contactable_id' => $contactable_id,
        'contactable_type' => $contactable_type,
    ]);

    expect(Contact::all())->toHaveCount(3);
    expect(Contact::query()->where('contactable_type', CharacterInfo::class)->get())
        ->toHaveCount(1);
    expect(Contact::query()->where('contactable_type', $contactable_type)->get())
        ->toHaveCount(2);

    // Then for the test user get the contact and see the standing
    $result = Contact::query()
        ->where('contactable_id', $this->test_character->character_id)
        ->withStandings($this->test_character->corporation->corporation_id, $contactable_type === AllianceInfo::class ? $contactable_id : null)
        ->get();

    expect($result)
        ->toHaveCount(1)
        ->each(function($contact) use ($contactable_type) {

            $contact->standing->toBe(10.0);

            match ($contactable_type) {
                CorporationInfo::class => $contact->corporation_standing->toBe(-10.0),
                AllianceInfo::class => $contact->alliance_standing->toBe(-10.0),
            };

        });

})->with([
    [fn() => $this->test_character->corporation->corporation_id, CorporationInfo::class],
    [fn() => $this->test_character->alliance->alliance_id, AllianceInfo::class]
]);