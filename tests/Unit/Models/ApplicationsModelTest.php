<?php

use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;

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

test('it has decision count attribute based on log entries', function () {
    $application = Application::factory()->create([
        'corporation_id' => $this->test_character->corporation->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    expect($application)
        ->log_entries->toHaveCount(0)
        ->decision_count->toBe(0);

    $application->log_entries()->create([
        'causer_type' => User::class,
        'causer_id' => 1,
        'type' => 'decision',
        'comment' => 'test_comment',
    ]);

    $application->log_entries()->create([
        'causer_type' => User::class,
        'causer_id' => 1,
        'type' => 'comment',
        'comment' => 'test_comment',
    ]);

    expect($application->refresh())
        ->log_entries->toHaveCount(2)
        ->decision_count->toBe(1);
});

it('has enlistment relationship', function () {
    $enlistment = Enlistments::query()->create([
        'corporation_id' => CorporationInfo::first()->corporation_id,
        'type' => 'user',
        'steps' => '',
    ]);

    $application = Application::factory()->create([
        'corporation_id' => $enlistment->corporation_id,
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => $this->test_character->character_id,
    ]);

    expect($application)->enlistment->toBeInstanceOf(Enlistments::class);
});
