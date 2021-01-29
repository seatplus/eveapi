<?php


namespace Seatplus\Eveapi\Tests\Jobs\Universe;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ResolveLocationJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    public function setUp(): void
    {

        parent::setUp();

        Event::fake([
            UniverseStationCreated::class,
            UniverseStructureCreated::class,
            RefreshTokenCreated::class
        ]);
    }

    private function buildJob(int $location_id) : ResolveLocationJob
    {
        $refresh_token = factory(RefreshToken::class)->create([
            'scopes' => ['esi-universe.read_structures.v1']
        ]);

        return new ResolveLocationJob($location_id, $refresh_token);
    }

    /** @test */
    public function it_checks_only_asset_safety_checker()
    {
        Asset::factory()->create([
            'location_id' => 2004,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'other'
        ]);

        $this->buildJob(2004)->handle();

        $this->assertNull(Location::find(2004));
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_station_checker()
    {
        $test_assets = Asset::factory()->create([
            'location_id' => 60003760,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station'
        ]);

        $mock_data = factory(Station::class)->make([
            'station_id' => 60003760,
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->buildJob(60003760)->handle();

        $this->assertNotNull(Location::find(60003760)->locatable);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_station_older_then_a_week()
    {

        $location_id = 60003760;

        Asset::factory()->count(5)->create([
            'location_id' => $location_id,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        $mock_data = factory(Station::class)->create([
            'station_id' => $location_id,
            'updated_at' => carbon()->subWeeks(2)
        ]);

        factory(Location::class)->create([
            'location_id' => $location_id,
            'locatable_id' => $location_id,
            'locatable_type' => Station::class
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isBefore(carbon()->subWeek()));

        $this->buildJob($location_id)->handle();

        $this->assertNotNull(Location::find($location_id)->locatable);

        $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));
    }

    /** @test */
    public function it_checks_no_station_younger_then_a_week()
    {
        $location_id = 60003760;

        Asset::factory()->count(5)->create([
            'location_id' => $location_id,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        factory(Station::class)->create([
            'station_id' => $location_id,
            'updated_at' => carbon()->subDays(2)
        ]);

        factory(Location::class)->create([
            'location_id' => $location_id,
            'locatable_id' => $location_id,
            'locatable_type' => Station::class
        ]);

        $this->buildJob($location_id)->handle();

        $this->assertNotNull(Location::find($location_id)->locatable);
        $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));
        $this->assertTrue(carbon(Station::find($location_id)->updated_at)->isBefore(carbon()->subDay()));
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_structure_checker()
    {
        $test_assets = Asset::factory()->count(5)->create([
            'location_id' => 1028832949394,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station'
        ]);

        $mock_data = factory(Structure::class)->make([
            'station_id' => 1028832949394,
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->buildJob(1028832949394)->handle();

        $this->assertNotNull(Location::find(1028832949394)->locatable);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_structure_older_then_a_week()
    {
        $location_id = 1028832949394;

        Asset::factory()->count(5)->create([
            'location_id' => $location_id,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        $mock_data = factory(Structure::class)->create([
            'structure_id' => $location_id,
            'updated_at' => carbon()->subWeeks(2)
        ]);

        factory(Location::class)->create([
            'location_id' => $location_id,
            'locatable_id' => $location_id,
            'locatable_type' => Structure::class
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isBefore(carbon()->subWeek()));

        $this->buildJob($location_id)->handle();

        $this->assertNotNull(Location::find($location_id)->locatable);

        $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));

    }

    /** @test */
    public function it_checks_no_structure_younger_then_a_week()
    {
        $location_id = 1028832949394;

        Asset::factory()->count(5)->create([
            'location_id' => $location_id,
            'assetable_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'other',
        ]);

        factory(Structure::class)->create([
            'structure_id' => $location_id,
            'updated_at' => carbon()->subDays(2)
        ]);

        factory(Location::class)->create([
            'location_id' => $location_id,
            'locatable_id' => $location_id,
            'locatable_type' => Structure::class
        ]);

        $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));

        $this->buildJob($location_id)->handle();

        $this->assertNotNull(Location::find($location_id)->locatable);

        $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isAfter(carbon()->subWeek()));
        $this->assertTrue(carbon(Structure::find($location_id)->updated_at)->isBefore(carbon()->subDay()));
    }

}
