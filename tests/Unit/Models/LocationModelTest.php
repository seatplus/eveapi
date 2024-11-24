<?php

use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;

it('has filter by region ids scope', function () {

    // Arrange
    $system = System::factory()->create();
    $station = Station::factory()->create([
        'system_id' => $system->system_id,
    ]);
    $location = Location::factory()->create([
        'locatable_id' => $station->station_id,
        'locatable_type' => Station::class,
    ]);

    // Act
    $locations = Location::filterByRegionIds($system->region->region_id)->get();

    // Assert
    expect($locations->count())->toBeGreaterThan(0);
});

it('has filter by system ids scope', function () {

    // Arrange
    $system = System::factory()->create();
    $station = Station::factory()->create([
        'system_id' => $system->system_id,
    ]);
    $location = Location::factory()->create([
        'locatable_id' => $station->station_id,
        'locatable_type' => Station::class,
    ]);

    // Act
    $locations = Location::filterBySystemIds($system->system_id)->get();

    // Assert
    expect($locations->count())->toBeGreaterThan(0);
});

it('has assets relationship', function () {

    // Arrange
    $system = System::factory()->create();
    $station = Station::factory()->create([
        'system_id' => $system->system_id,
    ]);
    $location = Location::factory()->create([
        'locatable_id' => $station->station_id,
        'locatable_type' => Station::class,
    ]);

    \Seatplus\Eveapi\Models\Assets\Asset::factory()->create([
        'location_id' => $location->location_id,
    ]);

    // Act
    $assets = $location->assets;

    // Assert
    expect($assets->count())->toBeGreaterThan(0);
});
