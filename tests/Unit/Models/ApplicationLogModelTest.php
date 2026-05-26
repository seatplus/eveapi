<?php

use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

it('has causer morph relationshio', function () {
    $application = Application::factory()->create();

    $log = $application->log_entries()->create([
        'causer_type' => CharacterInfo::class,
        'causer_id' => test()->test_character->character_id,
        'type' => faker()->randomElement(['decision', 'comment']),
        'comment' => faker()->text,
    ]);

    expect($log)->causer->toBeInstanceOf(CharacterInfo::class)
        ->and($log->application)->toBeInstanceOf(Application::class);
});
