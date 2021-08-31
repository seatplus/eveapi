<?php


use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
});

it('has constellation', function () {
    $system = System::factory()->create();

    $this->assertInstanceOf(Constellation::class, $system->constellation);
});

it('has stations', function () {
    $system = System::factory()
        ->hasStations(1)
        ->hasStructures(1)
        ->create();

    $this->assertInstanceOf(Structure::class, $system->structures->first());
});

it('has region', function () {
    $system = System::factory()->create();

    $this->assertInstanceOf(Region::class, $system->constellation->region);

    $this->assertInstanceOf(Region::class, $system->region);
});
