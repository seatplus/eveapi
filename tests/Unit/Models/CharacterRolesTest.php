<?php

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;

test('character has character roles relation test', function () {
    expect($this->test_character->roles)->toBeInstanceOf(CharacterRole::class);
});

test('character role has character relation test', function () {
    $character_role = $this->test_character->roles;

    expect($character_role->character)->toBeInstanceOf(CharacterInfo::class);
});

test('has role test', function () {
    $character_role = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager'],
    ]);

    expect($character_role->hasRole('roles', 'Contract_Manager'))->toBeTrue();
});

test('has director role test', function () {
    $character_role = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
    ]);

    expect($character_role->hasRole('roles', 'Hangar_Query_3'))->toBeTrue();
});

test('has no role in scope', function () {
    $character_role = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
        'roles_at_hq' => ['Hangar_Query_3'],
    ]);

    expect($character_role->hasRole('roles_at_hq', 'Contract_Manager'))->toBeFalse();
});
