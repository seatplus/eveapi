<?php


namespace Seatplus\Eveapi\Tests\Integration;


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
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class UniverseRegionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /** @test */
    public function universe_station_creation_creates_event()
    {
        Event::fake();

        $station = Station::factory()->create();

        Event::assertDispatched(UniverseStationCreated::class);

    }

    /** @test */
    public function it_dispatches_resolver_job()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $station = Station::factory()->noSystem()->create();

        Queue::assertPushedOn('default', ResolveUniverseSystemBySystemIdJob::class);
    }

    /** @test */
    public function it_does_not_dispatches_resolver_job_if_system_exists()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $station = Station::factory()->create();

        Queue::assertNotPushed(ResolveUniverseSystemBySystemIdJob::class);
    }

    /** @test */
    public function it_resolves_system()
    {

        $mock_data = System::factory()->make();
        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        //$job = new ResolveUniverseSystemBySystemIdJob;

        //Assert that no system is created
        $this->assertDatabaseMissing('universe_systems', [
            'system_id' => $mock_data->system_id
        ]);

        Event::fake();

        //$job->setSystemId($mock_data->system_id)->handle();
        (new ResolveUniverseSystemBySystemIdJob($mock_data->system_id))->handle();

        Event::assertDispatched(UniverseSystemCreated::class);

        //Assert that system is created
        $this->assertDatabaseHas('universe_systems', [
            'system_id' => $mock_data->system_id
        ]);

    }

    public function event_starts_dispatcher()
    {
        $system = System::factory()->noConstellation()->make();

        Queue::fake();

        event(new UniverseSystemCreated($system));

        Queue::assertPushedOn('default', ResolveUniverseConstellationByConstellationIdJob::class);
    }

    /** @test */
    public function universe_structure_creation_creates_event()
    {
        Event::fake();

        $station = Structure::factory()->create();

        Event::assertDispatched(UniverseStructureCreated::class);
    }

    /** @test */
    public function universe_system_creation_creates_event()
    {
        Event::fake();

        $station = System::factory()->create();

        Event::assertDispatched(UniverseSystemCreated::class);
    }

    /** @test */
    public function it_does_not_dispatches_constellation_resolver_job_if_system_exists()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $station = System::factory()->create();

        Queue::assertNotPushed(ResolveUniverseConstellationByConstellationIdJob::class);
    }

    /** @test */
    public function it_resolves_constellations()
    {
        $mock_data = Constellation::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        //Assert that no system is present
        $this->assertDatabaseMissing('universe_constellations', [
            'constellation_id' => $mock_data->constellation_id
        ]);

        (new ResolveUniverseConstellationByConstellationIdJob($mock_data->constellation_id))->handle();

        //Assert that system is created
        $this->assertDatabaseHas('universe_constellations', [
            'constellation_id' => $mock_data->constellation_id
        ]);

    }

    /** @test */
    public function universe_constellation_creation_creates_event()
    {
        Event::fake();

        Constellation::factory()->create();

        Event::assertDispatched(UniverseConstellationCreated::class);
    }

    /** @test */
    public function it_dispatches_region_resolver()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $station = Constellation::factory()->noRegion()->create();

        Queue::assertPushedOn('default', ResolveUniverseRegionByRegionIdJob::class);
    }

    /** @test */
    public function it_does_not_dispatches_region_resolver_job_if_region_exists()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $station = Constellation::factory()->create();

        Queue::assertNotPushed(ResolveUniverseRegionByRegionIdJob::class);
    }

    /** @test */
    public function it_resolves_regions()
    {
        $mock_data = Region::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        //Assert that no system is present
        $this->assertDatabaseMissing('universe_regions', [
            'region_id' => $mock_data->region_id
        ]);

        (new ResolveUniverseRegionByRegionIdJob($mock_data->region_id))->handle();

        //Assert that system is created
        $this->assertDatabaseHas('universe_regions', [
            'region_id' => $mock_data->region_id
        ]);

    }



}
