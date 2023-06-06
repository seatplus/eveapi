<?php

use Faker\Factory;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;

beforeEach(function () {
    Queue::fake();
});

test('character has refresh token relation test', function () {
    expect($this->test_character->refresh_token)->toBeInstanceOf(RefreshToken::class);
});

test('character has alliance relation test', function () {
    $faker = Factory::create();

    $alliance_id = $faker->numberBetween(99000000, 100000000);

    $affiliation = CharacterAffiliation::factory()->create([
        'alliance_id' => $alliance_id,
    ]);

    $character = $affiliation->character()->save(CharacterInfo::factory()->create());

    $affiliation->alliance()->associate(AllianceInfo::factory()->create([
        'alliance_id' => $alliance_id,
    ]));

    expect($character->alliance)->toBeInstanceOf(AllianceInfo::class);
});

test('character has corporation relation test', function () {
    expect($this->test_character->corporation)->toBeInstanceOf(CorporationInfo::class);
});

it('has application relationship', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    expect($this->test_character->application)->toBeInstanceOf(Application::class);
});

it('has asset relationship', function () {
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'assetable_type' => CharacterInfo::class,
    ]);

    expect($this->test_character->refresh()->assets->first())->toBeInstanceOf(Asset::class);
});

test('upon creation dispatch affiliation job', function () {
    Queue::fake();

    $character = CharacterInfo::factory()->create();

    Queue::assertPushedOn('high', CharacterAffiliationJob::class);
});

it('has contract relationship', function () {
    $contract = Contract::factory()->create();
    $this->test_character->contracts()->attach($contract->contract_id);

    expect($this->test_character->refresh()->contracts->first())->toBeInstanceOf(Contract::class);

    // Test reverse too
    expect($contract->characters->first())->toBeInstanceOf(CharacterInfo::class);
});

test('character has balance relationship', function () {
    $balance = Balance::factory()->withDivision()->create([
        'balanceable_id' => $this->test_character->character_id,
        'balanceable_type' => CharacterInfo::class,
    ]);

    expect($this->test_character->refresh()->balance)->toBeInstanceOf(Balance::class);
});
