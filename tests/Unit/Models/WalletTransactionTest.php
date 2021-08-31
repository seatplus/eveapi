<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('character has wallet journal test', function () {
    $this->assertCount(0, $this->test_character->wallet_transactions);

    $wallet_transaction = Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
        'wallet_transactionable_type' => CharacterInfo::class,
    ]));

    $this->assertInstanceOf(CharacterInfo::class, $wallet_transaction->wallet_transactionable);

    $character = $this->test_character->refresh();
    $this->assertCount(1, $character->wallet_transactions);
    $this->assertInstanceOf(WalletTransaction::class, $character->wallet_transactions->first());
});

test('corporation has relationships test', function () {
    $this->assertCount(0, $this->test_character->wallet_transactions);

    $wallet_transaction = Event::fakeFor(fn () => WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->character_id,
        'wallet_transactionable_type' => CharacterInfo::class,
        'type_id' => Type::factory()->create(),
        'location_id' => Location::factory()->create(),
    ]));

    $this->assertInstanceOf(Type::class, $wallet_transaction->type);
    $this->assertInstanceOf(Location::class, $wallet_transaction->location);
});
