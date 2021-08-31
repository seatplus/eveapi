<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\Mail\MailRecipients;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('character has mails test', function () {
    expect($this->test_character->mails)->toHaveCount(0);

    $mail = Event::fakeFor(fn () => Mail::factory()->create());
    $mail_receipient = Event::fakeFor(fn () => MailRecipients::factory()->create([
        'mail_id' => $mail->id,
        'receivable_id' => $this->test_character->character_id,
        'receivable_type' => CharacterInfo::class,
    ]));

    expect($mail_receipient->mail)->toBeInstanceOf(Mail::class);
    expect($mail_receipient->receivable)->toBeInstanceOf(CharacterInfo::class);

    $character = $this->test_character->refresh();

    expect($character->mails)->toHaveCount(1);
    expect($character->mails->first())->toBeInstanceOf(Mail::class);
});
