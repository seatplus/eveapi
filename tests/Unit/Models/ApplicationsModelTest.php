<?php


use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('character has application relationship', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    $this->assertInstanceOf(CharacterInfo::class, $application->applicationable);
    $this->assertInstanceOf(Application::class, $this->test_character->application);
});

it('has corporation relationship', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    $this->assertInstanceOf(CorporationInfo::class, $application->corporation);
});

test('create application through character', function () {
    $this->test_character->application()->create(['corporation_id' => $this->test_character->corporation->corporation_id]);

    $this->assertInstanceOf(Application::class, $this->test_character->application);
});

test('has of corporation scope', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    $this->assertInstanceOf(CharacterInfo::class, Application::ofCorporation($this->test_character->corporation->corporation_id)->first()->applicationable);
});
