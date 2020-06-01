<?php


namespace Seatplus\Eveapi\Tests\Jobs\Seatplus;


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Tests\TestCase;

class MaintenanceJobTest extends TestCase
{
    /**
     * @var \Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob
     */
    private MaintenanceJob $job;

    public function setUp(): void
    {

        parent::setUp();

        $this->job = new MaintenanceJob;

    }

    /** @test */
    public function it_fetches_missing_types_from_assets()
    {
        $asset = factory(CharacterAsset::class)->create();

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertTrue(in_array($asset->type_id,cache('type_ids_to_resolve')));
    }

    /** @test */
    public function it_fetches_missing_types_from_locations()
    {
        $station = factory(Station::class)->create();
        $location = factory(Location::class)->create([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]);

        $this->job->handle();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertTrue(in_array($station->type_id, cache('type_ids_to_resolve')));
    }



}
