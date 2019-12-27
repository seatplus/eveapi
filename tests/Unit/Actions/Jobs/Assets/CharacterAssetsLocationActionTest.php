<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Assets;


use Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsLocationAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterAssetsLocationActionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /**
     * @var \Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsLocationAction
     */
    private $action;

    public function setUp(): void
    {

        parent::setUp();

        $this->action = new CharacterAssetsLocationAction($this->test_character->refresh_token);
    }

    /** @test */
    public function it_builds_location_id()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'other'
        ]);

        $this->action->buildLocationIds();

        foreach ($test_assets as $test_asset)
            $this->assertTrue(in_array($test_asset->location_id, $this->action->getLocationIds()->toArray()));
    }

    /** @test */
    public function it_checks_only_asset_safety_checker()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 2004,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'other'
        ]);

        $this->action->buildLocationIds()->execute();

        $this->assertNull(Location::find(2004));
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_station_checker()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 60003760,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station'
        ]);

        $mock_data = factory(Station::class)->make([
            'station_id' => 60003760,
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->action->buildLocationIds()->execute();

        $this->assertNotNull(Location::find(60003760));
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_station_older_then_a_wek()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 60003760,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        $mock_data = factory(Station::class)->create([
            'station_id' => 60003760
        ])->location()->save(factory(Location::class)->create([
            'location_id' => 60003760,
            'updated_at' => carbon()->subWeeks(2)
        ]));

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->action->buildLocationIds()->execute();

        $this->assertNotNull(Location::find(60003760));
    }

    /** @test */
    public function it_checks_no_station_younger_then_a_wek()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 60003760,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        factory(Station::class)->create([
            'station_id' => 60003760
        ])->location()->save(factory(Location::class)->create([
            'location_id' => 60003760,
            'updated_at' => carbon()->subDays(2)
        ]));

        $this->action->buildLocationIds()->execute();

        $this->assertNotNull(Location::find(60003760));
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_structure_checker()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 1028832949394,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station'
        ]);

        $mock_data = factory(Structure::class)->make([
            'station_id' => 1028832949394,
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->action->buildLocationIds()->execute();

        $this->assertNotNull(Location::find(1028832949394));
    }


    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_checks_only_structure_older_then_a_wek()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 1028832949394,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        $mock_data = factory(Station::class)->create([
            'station_id' => 1028832949394
        ])->location()->save(factory(Location::class)->create([
            'location_id' => 1028832949394,
            'updated_at' => carbon()->subWeeks(2)
        ]));

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $this->action->buildLocationIds()->execute();

        $this->assertNotNull(Location::find(1028832949394));
    }

    /** @test */
    public function it_checks_no_structure_younger_then_a_wek()
    {
        $test_assets = factory(CharacterAsset::class,5)->create([
            'location_id' => 1028832949394,
            'character_id' => $this->test_character->character_id,
            'location_flag' => 'Hangar',
            'location_type' => 'station',
        ]);

        factory(Station::class)->create([
            'station_id' => 1028832949394
        ])->location()->save(factory(Location::class)->create([
            'location_id' => 1028832949394,
            'updated_at' => carbon()->subDays(2)
        ]));

        $this->action->buildLocationIds()->execute();

        $this->assertNotNull(Location::find(1028832949394));
    }

}
