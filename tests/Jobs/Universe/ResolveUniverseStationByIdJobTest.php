<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    $this->refresh_token = $this->test_character->refresh_token;

    Event::fake([
        UniverseStationCreated::class,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('creates station', function () {
    $mock_data = buildMockEsiData();

    $this->assertNull(Station::find($mock_data->station_id));


    (new ResolveUniverseStationByIdJob($mock_data->station_id))->handle();

    //Assert that structure is created
    $this->assertDatabaseHas('universe_stations', [
        'station_id' => $mock_data->station_id,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('creates location', function () {
    $mock_data = buildMockEsiData();

    //Assert that no structure is created
    $this->assertDatabaseMissing('universe_locations', [
        'location_id' => $mock_data->station_id,
    ]);

    (new ResolveUniverseStationByIdJob($mock_data->station_id))->handle();

    //Assert that structure is created
    $this->assertDatabaseHas('universe_locations', [
        'location_id' => $mock_data->station_id,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('creates polymorphic relationship', function () {
    $mock_data = buildMockEsiData();

    (new ResolveUniverseStationByIdJob($mock_data->station_id))->handle();

    $location = Location::find($mock_data->station_id);

    $this->assertInstanceOf(Station::class, $location->locatable);
});

/**
 * @runTestsInSeparateProcesses
 */
it('does not create structure if location id is not in range', function () {
    $mock_data = Station::factory()->make([
        'station_id' => 1234,
    ]);

    (new ResolveUniverseStationByIdJob($mock_data->station_id))->handle();

    //Assert that no structure is created
    $this->assertDatabaseMissing('universe_stations', [
        'station_id' => $mock_data->station_id,
    ]);
});

// Helpers
function buildMockEsiData()
{
    $mock_data = Station::factory()->make();

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
