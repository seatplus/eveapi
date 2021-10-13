<?php


use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;

beforeEach(function () {
});

it('has constellation', function () {
    $system = System::factory()->create();

    expect($system->constellation)->toBeInstanceOf(Constellation::class);
});

it('has stations', function () {
    $system = System::factory()
        ->hasStations(1)
        ->hasStructures(1)
        ->create();

    expect($system->structures->first())->toBeInstanceOf(Structure::class);
});

it('has region', function () {
    $system = System::factory()->create();

    expect($system->constellation->region)->toBeInstanceOf(Region::class);

    expect($system->region)->toBeInstanceOf(Region::class);
});
