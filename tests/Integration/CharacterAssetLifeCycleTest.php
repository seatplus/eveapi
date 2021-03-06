<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetLifeCycleTest extends TestCase
{

    /** @test */
    public function it_dispatches_type_job()
    {
        $asset = Asset::factory()->make();

        Queue::assertNotPushed('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertDatabaseMissing('assets', ['item_id' => $asset->item_id]);

        Asset::updateOrCreate([
            'item_id' => $asset->item_id,
        ], [
            'assetable_id' => $asset->assetable_id,
            'assetable_type' => CharacterInfo::class,
            'is_blueprint_copy' => optional($asset)->is_blueprint_copy ?? false,
            'is_singleton'  => $asset->is_singleton,
            'location_flag'     => $asset->location_flag,
            'location_id'        => $asset->location_id,
            'location_type'          => $asset->location_type,
            'quantity'   => $asset->quantity,
            'type_id' => $asset->type_id,
        ]);

        $this->assertDatabaseHas('assets', ['item_id' => $asset->item_id]);

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        Queue::assertPushed(ResolveUniverseTypesByTypeIdJob::class, function ($job) use ($asset){
            return in_array(sprintf('type_id:%s', $asset->type_id), $job->tags());
        });
    }

    /** @test */
    public function it_does_not_dispatch_type_job_if_type_is_known()
    {

        $type = Type::factory()->create();

        $asset = Asset::factory()->create([
            'type_id' => $type->type_id
        ]);

        Queue::assertNotPushed(ResolveUniverseTypesByTypeIdJob::class);
    }

    /** @test */
    public function it_dispatches_location_job()
    {
        $asset = Asset::factory()->create();

        Queue::assertPushedOn('high', CharacterAssetsLocationJob::class);
    }

    /** @test */
    public function it_does_not_dispatch_location_job_if_location_is_known()
    {

        $location = Location::factory()->create();

        $asset = Asset::factory()->create([
            'location_id' => $location->location_id
        ]);

        Queue::assertNotPushed(CharacterAssetsLocationJob::class);
    }

    /** @test */
    public function it_dispatches_location_job_if_location_is_updating()
    {

        $asset = Event::fakeFor( function () {
            return Asset::factory()->create([
                'location_id' => 1234
            ]);
        });

        Queue::assertNotPushed(CharacterAssetsLocationJob::class);

        $asset->location_id = 56789;
        $asset->save();

        Queue::assertPushedOn('high', CharacterAssetsLocationJob::class);


    }


}
