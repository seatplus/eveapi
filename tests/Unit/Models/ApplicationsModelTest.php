<?php


use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

test('character has application relationship', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    expect($application->applicationable)->toBeInstanceOf(CharacterInfo::class);
    expect($this->test_character->application)->toBeInstanceOf(Application::class);
});

it('has corporation relationship', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    expect($application->corporation)->toBeInstanceOf(CorporationInfo::class);
});

test('create application through character', function () {
    $this->test_character->application()->create(['corporation_id' => $this->test_character->corporation->corporation_id]);

    expect($this->test_character->application)->toBeInstanceOf(Application::class);
});

test('has of corporation scope', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    expect(Application::ofCorporation($this->test_character->corporation->corporation_id)->first()->applicationable)->toBeInstanceOf(CharacterInfo::class);
});
