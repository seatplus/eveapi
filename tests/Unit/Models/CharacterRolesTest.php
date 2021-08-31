<?php


use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('character has character roles relation test', function () {
    $this->assertInstanceOf(CharacterRole::class, $this->test_character->roles);
});

test('character role has character relation test', function () {
    $character_role = $this->test_character->roles;

    $this->assertInstanceOf(CharacterInfo::class, $character_role->character);
});

test('has role test', function () {
    $character_role = CharacterRole::factory()->make([
        'roles' => ["Contract_Manager"],
    ]);

    $this->assertTrue($character_role->hasRole('roles', 'Contract_Manager'));
});

test('has director role test', function () {
    $character_role = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
    ]);

    $this->assertTrue($character_role->hasRole('roles', 'Hangar_Query_3'));
});

test('has no made up role', function () {
    $character_role = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
    ]);

    $this->assertFalse($character_role->hasRole('roles', 'Something_Made_up'));
});
