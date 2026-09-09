<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StationResolver;

beforeEach(function () {
    Event::fake();
});

it('never hands out the same primary key twice', function () {
    // 500 draws out of the old random 0-10000 range collided with probability 1 - 4e-6 (issue #728).
    $locationIds = collect(Location::factory()->count(500)->make())->pluck('location_id');

    expect($locationIds->unique())->toHaveCount(500);
});

it('does not reuse ids of locations that have already been persisted', function () {
    $first = Location::factory()->count(50)->create()->pluck('location_id');

    // Rows already in the table — the case fake()->unique() cannot see.
    $second = Location::factory()->count(50)->create()->pluck('location_id');

    expect($first->intersect($second))->toBeEmpty();
});

it('generates ids that are neither stations nor structures', function () {
    // The resolvers branch on the id itself, so the default has to stay out of both bands.
    $locationIds = Location::factory()->count(25)->create()->pluck('location_id');

    expect($locationIds)->each->toBeLessThan(StationResolver::MIN_STATION_ID);
});

it('still takes its id from the station when using the withStation state', function () {
    $location = Location::factory()->withStation()->create();

    expect($location->location_id)
        ->toBeGreaterThan(StationResolver::MIN_STATION_ID)
        ->toBeLessThan(StationResolver::MAX_STATION_ID)
        ->and(Station::query()->whereKey($location->location_id)->exists())->toBeTrue();
});
