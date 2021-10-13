<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

test('universe station creation creates event', function () {
    Event::fake();

    $station = Station::factory()->create();

    Event::assertDispatched(UniverseStationCreated::class);
});

it('dispatches resolver job', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $station = Station::factory()->noSystem()->create();

    Queue::assertPushedOn('default', ResolveUniverseSystemBySystemIdJob::class);
});

it('does not dispatches resolver job if system exists', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $station = Station::factory()->create();

    Queue::assertNotPushed(ResolveUniverseSystemBySystemIdJob::class);
});

it('resolves system', function () {
    $mock_data = System::factory()->make();
    mockRetrieveEsiDataAction($mock_data->toArray());

    //$job = new ResolveUniverseSystemBySystemIdJob;

    //Assert that no system is created
    $this->assertDatabaseMissing('universe_systems', [
        'system_id' => $mock_data->system_id,
    ]);

    Event::fake();

    //$job->setSystemId($mock_data->system_id)->handle();
    (new ResolveUniverseSystemBySystemIdJob($mock_data->system_id))->handle();

    Event::assertDispatched(UniverseSystemCreated::class);

    //Assert that system is created
    $this->assertDatabaseHas('universe_systems', [
        'system_id' => $mock_data->system_id,
    ]);
});

test('universe structure creation creates event', function () {
    Event::fake();

    $station = Structure::factory()->create();

    Event::assertDispatched(UniverseStructureCreated::class);
});

test('universe system creation creates event', function () {
    Event::fake();

    $station = System::factory()->create();

    Event::assertDispatched(UniverseSystemCreated::class);
});

it('does not dispatches constellation resolver job if system exists', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $station = System::factory()->create();

    Queue::assertNotPushed(ResolveUniverseConstellationByConstellationIdJob::class);
});

it('resolves constellations', function () {
    $mock_data = Constellation::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    //Assert that no system is present
    $this->assertDatabaseMissing('universe_constellations', [
        'constellation_id' => $mock_data->constellation_id,
    ]);

    (new ResolveUniverseConstellationByConstellationIdJob($mock_data->constellation_id))->handle();

    //Assert that system is created
    $this->assertDatabaseHas('universe_constellations', [
        'constellation_id' => $mock_data->constellation_id,
    ]);
});

test('universe constellation creation creates event', function () {
    Event::fake();

    Constellation::factory()->create();

    Event::assertDispatched(UniverseConstellationCreated::class);
});

it('dispatches region resolver', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $station = Constellation::factory()->noRegion()->create();

    Queue::assertPushedOn('default', ResolveUniverseRegionByRegionIdJob::class);
});

it('does not dispatches region resolver job if region exists', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $station = Constellation::factory()->create();

    Queue::assertNotPushed(ResolveUniverseRegionByRegionIdJob::class);
});

it('resolves regions', function () {
    $mock_data = Region::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    //Assert that no system is present
    $this->assertDatabaseMissing('universe_regions', [
        'region_id' => $mock_data->region_id,
    ]);

    (new ResolveUniverseRegionByRegionIdJob($mock_data->region_id))->handle();

    //Assert that system is created
    $this->assertDatabaseHas('universe_regions', [
        'region_id' => $mock_data->region_id,
    ]);
});

// Helpers
function event_starts_dispatcher()
{
    $system = System::factory()->noConstellation()->make();

    Queue::fake();

    event(new UniverseSystemCreated($system));

    Queue::assertPushedOn('default', ResolveUniverseConstellationByConstellationIdJob::class);
}
