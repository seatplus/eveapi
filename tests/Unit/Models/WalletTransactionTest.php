<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

test('character has wallet journal test', function () {
    expect($this->test_character->walletTransactions)->toHaveCount(0);

    $walletTransaction = Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
        'wallet_transactionable_type' => CharacterInfo::class,
    ]));

    expect($walletTransaction->walletTransactionable)->toBeInstanceOf(CharacterInfo::class);

    $character = $this->test_character->refresh();
    expect($character->walletTransactions)->toHaveCount(1);
    expect($character->walletTransactions->first())->toBeInstanceOf(WalletTransaction::class);
});

test('corporation has relationships test', function () {
    expect($this->test_character->walletTransactions)->toHaveCount(0);

    $walletTransaction = Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
        'wallet_transactionable_type' => CharacterInfo::class,
        'type_id' => Type::factory()->create(),
        'location_id' => Location::factory()->create(),
    ]));

    expect($walletTransaction->type)->toBeInstanceOf(Type::class);
    expect($walletTransaction->location)->toBeInstanceOf(Location::class);
});
