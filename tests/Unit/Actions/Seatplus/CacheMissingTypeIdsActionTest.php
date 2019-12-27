<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;

use Seatplus\Eveapi\Actions\Seatplus\CacheMissingTypeIdsAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Tests\TestCase;

class CacheMissingTypeIdsActionTest extends TestCase
{
    /**  */
    public function it_gets_missing_type_realtionships()
    {
        $assets = factory(CharacterAsset::class,5)->create();

        foreach ($assets as $asset)
            //Assert that test character is now created
            $this->assertDatabaseHas('character_assets', [
                'item_id' => $asset->item_id
            ]);

        $cached_type_ids = (new CacheMissingTypeIdsAction)->execute();

        $this->assertEquals($assets->pluck('type_id')->unique()->values(), $cached_type_ids);
    }

    /** @test */
    public function it_gets_missing_location_type_realtionships()
    {
        $stations = factory(Station::class,5)->create();

        foreach ($stations as $station) {

            //Assert that test character is now created
            $this->assertDatabaseHas('universe_stations', [
                'type_id' => $station->type_id
            ]);

            $location = Location::firstOrCreate(['location_id' => $station->station_id]);

            $station->location()->save($location);
        }


        $cached_type_ids = (new CacheMissingTypeIdsAction)->execute();

        $stations->pluck('type_id')->unique()->values()->each(function ($type_id) use ($cached_type_ids){
            $this->assertTrue(in_array($type_id, $cached_type_ids->toArray()));
        });

    }

    /** @test */
    public function it_creates_one_flat_chached_type_ids_object()
    {
        $stations = factory(Station::class,5)->create();
        $assets = factory(CharacterAsset::class,5)->create();

        foreach ($stations as $station) {

            $location = Location::firstOrCreate(['location_id' => $station->station_id]);

            $station->location()->save($location);
        }

        $cached_type_ids = (new CacheMissingTypeIdsAction)->execute();

        foreach ($stations as $station) {

            $this->assertTrue(in_array($station->type_id, $cached_type_ids->toArray()));
        }

        foreach ($assets as $asset) {

            $this->assertTrue(in_array($asset->type_id, $cached_type_ids->toArray()));
        }


    }

}