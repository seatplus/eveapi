<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;

test('has system relationship', function () {
    Event::fake([
        UniverseStructureCreated::class,
    ]);

    $structure = Structure::factory()->create();

    expect($structure->system)->toBeInstanceOf(System::class);
});

test('has location relationship', function () {
    Event::fake([
        UniverseStructureCreated::class,
    ]);

    $structure = Structure::factory()->create();
    $location = Location::factory()->create([
        'location_id' => $structure->structure_id,
        'locatable_id' => $structure->structure_id,
        'locatable_type' => Structure::class,
    ]);

    expect($structure->location)->toBeInstanceOf(Location::class);
});
