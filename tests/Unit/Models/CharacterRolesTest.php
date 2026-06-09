<?php

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;

test('character has character roles relation test', function () {
    expect($this->test_character->roles)->toBeInstanceOf(CharacterRole::class);
});

test('character role has character relation test', function () {
    $characterRole = $this->test_character->roles;

    expect($characterRole->character)->toBeInstanceOf(CharacterInfo::class);
});

test('has role test', function () {
    $characterRole = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager'],
    ]);

    expect($characterRole->hasRole('roles', 'Contract_Manager'))->toBeTrue();
});

test('has director role test', function () {
    $characterRole = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
    ]);

    expect($characterRole->hasRole('roles', 'Hangar_Query_3'))->toBeTrue();
});

test('has no role in scope', function () {
    $characterRole = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
        'roles_at_hq' => ['Hangar_Query_3'],
    ]);

    expect($characterRole->hasRole('roles_at_hq', 'Contract_Manager'))->toBeFalse();
});

it('returns false if scope is null', function () {
    $characterRole = CharacterRole::factory()->make([
        'roles' => ['Contract_Manager', 'Director'],
        'roles_at_hq' => null,
    ]);

    expect($characterRole->hasRole('roles_at_hq', 'Contract_Manager'))->toBeFalse();
});
