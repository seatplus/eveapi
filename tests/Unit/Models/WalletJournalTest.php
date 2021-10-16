<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

test('character has wallet journal test', function () {
    expect($this->test_character->wallet_journals)->toHaveCount(0);

    $wallet_journal = Event::fakeFor(fn () => WalletJournal::factory()->create([
        'wallet_journable_id' => $this->test_character->character_id,
        'wallet_journable_type' => CharacterInfo::class,
    ]));

    expect($wallet_journal->wallet_journable)->toBeInstanceOf(CharacterInfo::class);

    $character = $this->test_character->refresh();
    expect($character->wallet_journals)->toHaveCount(1);
    expect($character->wallet_journals->first())->toBeInstanceOf(WalletJournal::class);
});
