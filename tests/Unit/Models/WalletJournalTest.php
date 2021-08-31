<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('character has wallet journal test', function () {
    $this->assertCount(0, $this->test_character->wallet_journals);

    $wallet_journal = Event::fakeFor(fn () => WalletJournal::factory()->create([
        'wallet_journable_id' => $this->test_character->character_id,
        'wallet_journable_type' => CharacterInfo::class,
    ]));

    $this->assertInstanceOf(CharacterInfo::class, $wallet_journal->wallet_journable);

    $character = $this->test_character->refresh();
    $this->assertCount(1, $character->wallet_journals);
    $this->assertInstanceOf(WalletJournal::class, $character->wallet_journals->first());
});
