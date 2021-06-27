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
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Tests\TestCase;

class MailTest extends TestCase
{
    /** @test */
    public function character_has_mails_test()
    {

        $this->assertCount(0, $this->test_character->mails);

        $mail = Event::fakeFor( fn() =>  Mail::factory()->create());
        $mail_receipient = Event::fakeFor( fn() =>  MailRecipients::factory()->create([
            'mail_id' => $mail->id,
            'receivable_id' => $this->test_character->character_id,
            'receivable_type' => CharacterInfo::class,
        ]));

        $this->assertInstanceOf(Mail::class, $mail_receipient->mail);
        $this->assertInstanceOf(CharacterInfo::class, $mail_receipient->receivable);

        $character = $this->test_character->refresh();

        $this->assertCount(1, $character->mails);
        $this->assertInstanceOf(Mail::class, $character->mails->first());

    }


}
