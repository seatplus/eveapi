<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

beforeEach(function () {
    Event::fake([
        UniverseStationCreated::class,
    ]);
});

it('creates station', function () {
    $mock_data = Station::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new ResolveUniverseStationByIdJob($mock_data->station_id);
    $job->executeJob($esi);

    expect(Station::where('station_id', $mock_data->station_id)->exists())->toBeTrue();
});

it('creates location', function () {
    $mock_data = Station::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new ResolveUniverseStationByIdJob($mock_data->station_id);
    $job->executeJob($esi);

    expect(Location::where('location_id', $mock_data->station_id)->exists())->toBeTrue();
});

it('creates polymorphic relationship', function () {
    $mock_data = Station::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new ResolveUniverseStationByIdJob($mock_data->station_id);
    $job->executeJob($esi);

    $location = Location::find($mock_data->station_id);

    expect($location->locatable)->toBeInstanceOf(Station::class);
});

it('does not create structure if location id is not in range', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = new ResolveUniverseStationByIdJob(1234);
    $job->executeJob($esi);

    expect(Station::where('station_id', 1234)->exists())->toBeFalse();
});

it('skips db write when response is a cached load', function () {
    $mock_data = Station::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new ResolveUniverseStationByIdJob($mock_data->station_id);
    $job->executeJob($esi);

    expect(Station::where('station_id', $mock_data->station_id)->exists())->toBeFalse();
});
