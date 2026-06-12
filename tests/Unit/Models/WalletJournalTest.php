<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

test('character has wallet journal test', function () {
    expect($this->test_character->walletJournals)->toHaveCount(0);

    $walletJournal = Event::fakeFor(fn () => WalletJournal::factory()->create([
        'wallet_journable_id' => $this->test_character->character_id,
        'wallet_journable_type' => CharacterInfo::class,
    ]));

    expect($walletJournal->walletJournable)->toBeInstanceOf(CharacterInfo::class);

    $character = $this->test_character->refresh();
    expect($character->walletJournals)->toHaveCount(1);
    expect($character->walletJournals->first())->toBeInstanceOf(WalletJournal::class);
});
