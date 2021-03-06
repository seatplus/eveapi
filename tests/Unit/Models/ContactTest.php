<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Illuminate\Support\Facades\Event;
use Mockery;
use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Contact;
use Seatplus\Eveapi\Models\Contacts\ContactLabel;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class ContactTest extends TestCase
{
    /** @test */
    public function character_has_contact_test()
    {

        $this->assertCount(0, $this->test_character->contacts);

        $contact = Event::fakeFor( fn() =>  Contact::factory()->create([
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class
        ]));

        $this->assertInstanceOf(CharacterInfo::class, $contact->contactable);

        $character = $this->test_character->refresh();
        $this->assertCount(1, $character->contacts);
        $this->assertInstanceOf(Contact::class, $character->contacts->first());

    }

    /** @test */
    public function corporation_has_contact_test()
    {

        $this->assertCount(0, $this->test_character->corporation->contacts);

        $contact = Event::fakeFor( fn() => Contact::factory()->create([
            'contactable_id' => $this->test_character->corporation->corporation_id,
            'contactable_type' => CorporationInfo::class
        ]));

        $this->assertInstanceOf(CorporationInfo::class, $contact->contactable);

        $corporation = $this->test_character->corporation->refresh();
        $this->assertCount(1, $corporation->contacts);
        $this->assertInstanceOf(Contact::class, $corporation->contacts->first());
    }

    /** @test */
    public function alliance_has_contact_test()
    {

        $this->assertCount(0, $this->test_character->alliance->contacts);

        $contact = Event::fakeFor( fn() => Contact::factory()->create([
            'contactable_id' => $this->test_character->alliance->alliance_id,
            'contactable_type' => AllianceInfo::class
        ]));

        $this->assertInstanceOf(AllianceInfo::class, $contact->contactable);

        $alliance = $this->test_character->alliance->refresh();
        $this->assertCount(1, $alliance->contacts);
        $this->assertInstanceOf(Contact::class, $alliance->contacts->first());
    }

    /** @test */
    public function contact_has_label()
    {
        $contact = Event::fakeFor( fn() => Contact::factory()->create([
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class
        ]));


        $this->assertCount(0, $contact->labels);

        $contact_label = new ContactLabel(['contact_id' => $contact->id, 'label_id' => 1]);

        $contact_label->save();

        $label = Label::factory()->create([
            'labelable_id' => $this->test_character->character_id,
            'labelable_type' => CharacterInfo::class,
            'label_id' => 1
        ]);

        $this->assertNotNull(ContactLabel::first()->label_name);
    }

    /** @test */
    public function contact_has_character_affiliation()
    {
        $contact = Event::fakeFor( fn() => Contact::factory()->create([
            'contact_id' => $this->test_character->character_id,
            'contact_type' => 'character',
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class
        ]));

        $this->assertTrue($contact->affiliations instanceof CharacterAffiliation);
        $this->assertEquals($this->test_character->character_id, $contact->affiliations->character_id);
    }

    /** @test */
    public function contact_has_corporation_affiliation()
    {
        $contact = Event::fakeFor( fn() => Contact::factory()->create([
            'contact_id' => $this->test_character->corporation->corporation_id,
            'contact_type' => 'corporation',
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class
        ]));

        $this->assertTrue($contact->affiliations instanceof CharacterAffiliation);
        $this->assertEquals($this->test_character->corporation->corporation_id, $contact->affiliations->corporation_id);

    }

    /** @test */
    public function contact_has_alliance_affiliation()
    {
        $contact = Event::fakeFor( fn() => Contact::factory()->create([
            'contact_id' => $this->test_character->corporation->alliance_id,
            'contact_type' => 'alliance',
            'contactable_id' => $this->test_character->character_id,
            'contactable_type' => CharacterInfo::class
        ]));

        $this->assertTrue($contact->affiliations instanceof CharacterAffiliation);
        $this->assertEquals($this->test_character->corporation->alliance_id, $contact->affiliations->alliance_id);

    }

}
