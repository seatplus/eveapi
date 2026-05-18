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

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('universe->getUniverseStationsStationId', $dto);

    runJob(new ResolveUniverseStationByIdJob($mock_data->station_id));

    $this->assertDatabaseHas('universe_stations', [
        'station_id' => $mock_data->station_id,
    ]);
});

it('creates location', function () {
    $mock_data = Station::factory()->make();

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('universe->getUniverseStationsStationId', $dto);

    $this->assertDatabaseMissing('universe_locations', [
        'location_id' => $mock_data->station_id,
    ]);

    runJob(new ResolveUniverseStationByIdJob($mock_data->station_id));

    $this->assertDatabaseHas('universe_locations', [
        'location_id' => $mock_data->station_id,
    ]);
});

it('creates polymorphic relationship', function () {
    $mock_data = Station::factory()->make();

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('universe->getUniverseStationsStationId', $dto);

    runJob(new ResolveUniverseStationByIdJob($mock_data->station_id));

    $location = Location::find($mock_data->station_id);

    expect($location->locatable)->toBeInstanceOf(Station::class);
});

it('does not create structure if location id is not in range', function () {
    $esi = Mockery::mock(EsiClient::class);
    app()->instance(EsiClient::class, $esi);
    mockTokenService();

    runJob(new ResolveUniverseStationByIdJob(1234));

    $this->assertDatabaseMissing('universe_stations', [
        'station_id' => 1234,
    ]);
});
