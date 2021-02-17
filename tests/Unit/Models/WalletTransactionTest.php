<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;

class WalletTransactionTest extends TestCase
{
    /** @test */
    public function characterHasWalletJournalTest()
    {

        $this->assertCount(0, $this->test_character->wallet_transactions);

        $wallet_transaction = Event::fakeFor( fn() =>  WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id,
            'wallet_transactionable_type' => CharacterInfo::class
        ]));

        $this->assertInstanceOf(CharacterInfo::class, $wallet_transaction->wallet_transactionable);

        $character = $this->test_character->refresh();
        $this->assertCount(1, $character->wallet_transactions);
        $this->assertInstanceOf(WalletTransaction::class, $character->wallet_transactions->first());

    }

    /** @test */
    public function corporation_has_relationships_test()
    {

        $this->assertCount(0, $this->test_character->wallet_transactions);

        $wallet_transaction = Event::fakeFor( fn() => WalletTransaction::factory()->create([
            'wallet_transactionable_id' => $this->test_character->character_id,
            'wallet_transactionable_type' => CharacterInfo::class,
            'type_id' => Type::factory()->create(),
            'location_id' => Location::factory()->create()
        ]));

        $this->assertInstanceOf(Type::class, $wallet_transaction->type);
        $this->assertInstanceOf(Location::class, $wallet_transaction->location);
    }

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
