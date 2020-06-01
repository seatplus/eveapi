<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetLifeCycleTest extends TestCase
{

    /** @test */
    public function it_dispatches_type_job()
    {
        $asset = factory(CharacterAsset::class)->create();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);
    }

    /** @test */
    public function it_does_not_dispatch_type_job_if_type_is_known()
    {

        $type = factory(Type::class)->create();

        $asset = factory(CharacterAsset::class)->create([
            'type_id' => $type->type_id
        ]);

        Queue::assertNotPushed(ResolveUniverseTypesByTypeIdJob::class);
    }

    /** @test */
    public function it_dispatches_location_job()
    {
        $asset = factory(CharacterAsset::class)->create();

        Queue::assertPushedOn('high', CharacterAssetsLocationJob::class);
    }

    /** @test */
    public function it_does_not_dispatch_location_job_if_location_is_known()
    {

        $location = factory(Location::class)->create();

        $asset = factory(CharacterAsset::class)->create([
            'location_id' => $location->location_id
        ]);

        Queue::assertNotPushed(CharacterAssetsLocationJob::class);
    }

    /** @test */
    public function it_dispatches_location_job_if_location_is_updating()
    {

        $asset = Event::fakeFor( function () {
            return factory(CharacterAsset::class)->create([
                'location_id' => 1234
            ]);
        });

        Queue::assertNotPushed(CharacterAssetsLocationJob::class);

        $asset->location_id = 56789;
        $asset->save();

        Queue::assertPushedOn('high', CharacterAssetsLocationJob::class);


    }


}
