<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Tests\TestCase;

class WalletJournalTest extends TestCase
{
    /** @test */
    public function characterHasWalletJournalTest()
    {
        $this->assertCount(0, $this->test_character->wallet_journals);

        $wallet_journal = Event::fakeFor(fn () => WalletJournal::factory()->create([
            'wallet_journable_id' => $this->test_character->character_id,
            'wallet_journable_type' => CharacterInfo::class,
        ]));

        $this->assertInstanceOf(CharacterInfo::class, $wallet_journal->wallet_journable);

        $character = $this->test_character->refresh();
        $this->assertCount(1, $character->wallet_journals);
        $this->assertInstanceOf(WalletJournal::class, $character->wallet_journals->first());
    }

    /** @test */
    /*public function corporation_has_contact_test()
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
    }*/

    /** @test */
    /*public function alliance_has_contact_test()
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
    }*/

    /** @test */
    /*public function contact_has_label()
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
    }*/
}
