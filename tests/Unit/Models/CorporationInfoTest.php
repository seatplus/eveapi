<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contacts\Label;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

beforeEach(function () {
    Queue::fake();
});

test('character has corporation relation test', function () {
    $this->assertEquals(
        $this->test_character->corporation_id,
        $this->test_character->corporation->corporation_id
    );
});

test('database row is created', function () {
    $this->assertDatabaseHas('corporation_infos', [
        'corporation_id' => $this->test_character->corporation_id,
    ]);
});

test('create character corporation releation', function () {
    $character = CharacterInfo::factory()->make();

    $characterAffiliation = $character->characterAffiliation()->save(CharacterAffiliation::factory()->make());

    $characterAffiliation->corporation()->associate(CorporationInfo::factory()->create([
        'corporation_id' => $characterAffiliation->corporation_id,
    ]));

    $this->assertEquals(
        $character->corporation_id,
        $character->corporation->corporation_id
    );
});

test('create many character relation', function () {
    $corporation = CorporationInfo::factory()->create();

    $characters = CharacterAffiliation::factory()->count(3)->create([
        'corporation_id' => $corporation->corporation_id,
        'alliance_id' => $corporation->alliance_id,
    ]);

    foreach ($characters as $character) {
        $character->character()->save(CharacterInfo::factory()->create());
    }

    expect($corporation->characters()->count())->toEqual(3);
});

it('has morphable sso scope', function () {
    $corporationInfo = CorporationInfo::factory()
        ->hasSsoScopes()
        ->create();

    // $corporationInfo->ssoScopes()->save(SsoScopes::factory()->make());

    expect($corporationInfo->refresh()->ssoScopes)->toBeInstanceOf(SsoScopes::class);
});

it('has recruits relationship', function () {
    $corporationInfo = CorporationInfo::factory()->create();

    $app = Application::factory()->count(5)->create([
        'corporation_id' => $corporationInfo->corporation_id,
    ]);

    foreach ($corporationInfo->refresh()->candidates as $candidate) {
        expect($candidate)->toBeInstanceOf(Application::class);
    }

    expect($corporationInfo->refresh()->candidates->count())->toEqual(5);
});

it('has alliance relationship', function () {
    $corporationInfo = CorporationInfo::factory()->create([
        'alliance_id' => AllianceInfo::factory(),
    ]);

    expect($corporationInfo->alliance)->toBeInstanceOf(AllianceInfo::class);
});

it('has members relationship', function () {
    $memberTracking = CorporationMemberTracking::factory()->create([
        'character_id' => $this->test_character->character_id,
        'corporation_id' => $this->test_character->corporation->corporation_id,
    ]);

    expect($this->test_character->refresh()->corporation->members->first())->toBeInstanceOf(CorporationMemberTracking::class);
});

it('has wallets relationship', function () {
    $balance = Balance::factory()->withDivision()->create([
        'balanceable_id' => $this->test_character->corporation->corporation_id,
        'balanceable_type' => CorporationInfo::class,
    ]);

    expect($this->test_character->corporation->refresh()->wallets->first())->toBeInstanceOf(Balance::class);
});

it('has labels relationship', function () {
    Label::factory()->create([
        'labelable_id' => $this->test_character->corporation->corporation_id,
        'labelable_type' => CorporationInfo::class,
    ]);

    expect($this->test_character->corporation->refresh()->labels->first())->toBeInstanceOf(Label::class);
});

it('has wallet transactions relationship', function () {
    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $this->test_character->corporation->corporation_id,
        'wallet_transactionable_type' => CorporationInfo::class,
    ]);

    expect($this->test_character->corporation->refresh()->walletTransactions->first())->toBeInstanceOf(WalletTransaction::class);
});
