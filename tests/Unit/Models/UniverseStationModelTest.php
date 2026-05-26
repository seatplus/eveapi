<?php

use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;

test('character has corporation relation test', function () {
    $station = Station::factory()->create();

    expect($station->system)->toBeInstanceOf(System::class);
});

it('has location relation', function () {
    // Arrange
    $system = System::factory()->create();
    $station = Station::factory()->create([
        'system_id' => $system->system_id,
    ]);
    Location::factory()->create([
        'locatable_id' => $station->station_id,
        'locatable_type' => Station::class,
    ]);

    // Act
    $location = $station->location;

    // Assert
    expect($location)->toBeInstanceOf(Location::class);
});
