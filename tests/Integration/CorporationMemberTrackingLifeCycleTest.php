<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob as CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationMemberTrackingLifeCycleTest extends TestCase
{

    /** @test */
    public function it_dispatches_type_job()
    {
        $tracking = factory(CorporationMemberTracking::class)->make([
            'ship_type_id' => Type::factory()->make()
        ]);

        Queue::assertNotPushed('high', ResolveUniverseTypesByTypeIdJob::class);

        $this->assertDatabaseMissing('corporation_member_trackings', ['ship_type_id' => $tracking->ship_type_id]);

        $tracking->save();

        $this->assertDatabaseHas('corporation_member_trackings', ['ship_type_id' => $tracking->ship_type_id]);

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

        Queue::assertPushed(ResolveUniverseTypesByTypeIdJob::class, function ($job) use ($tracking){
            return in_array(sprintf('type_id:%s', $tracking->ship_type_id), $job->tags());
        });
    }

    /** @test */
    public function it_does_not_dispatch_type_job_if_type_is_known()
    {

        $type = Type::factory()->create();

        $tracking = factory(CorporationMemberTracking::class)->create([
            'ship_type_id' => $type->type_id
        ]);

        Queue::assertNotPushed(ResolveUniverseTypesByTypeIdJob::class);
    }

    /** @test */
    public function it_dispatches_location_job()
    {

        $tracking = factory(CorporationMemberTracking::class)->create([
            'character_id' => $this->test_character->character_id
        ]);

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }

    /** @test */
    public function it_does_not_dispatch_location_job_if_location_is_known()
    {

        $location = factory(Location::class)->create();

        $tracking = factory(CorporationMemberTracking::class)->create([
            'location_id' => $location->location_id
        ]);

        Queue::assertNotPushed(ResolveLocationJob::class);
    }

    /** @test */
    public function it_dispatches_location_job_if_location_is_updating()
    {

        $tracking = Event::fakeFor( function () {
            return factory(CorporationMemberTracking::class)->create([
                'location_id' => 1234
            ]);
        });

        Queue::assertNotPushed(ResolveLocationJob::class);

        $tracking->location_id = 56789;
        $tracking->save();

        Queue::assertPushedOn('high', ResolveLocationJob::class);

    }

    /** @test */
    public function it_dispatches_type_job_if_ship_is_updating()
    {

        $tracking = Event::fakeFor( function () {
            return factory(CorporationMemberTracking::class)->create([
                'ship_type_id' => 1234
            ]);
        });

        Queue::assertNotPushed(ResolveUniverseTypesByTypeIdJob::class);

        $tracking->ship_type_id = 56789;
        $tracking->save();

        Queue::assertPushedOn('high', ResolveUniverseTypesByTypeIdJob::class);

    }

    /** @test */
    public function it_does_not_dispatch_character_job_if_character_is_known()
    {

        $character = factory(CharacterInfo::class)->create();

        $tracking = factory(CorporationMemberTracking::class)->create([
            'character_id' => $character->character_id
        ]);

        Queue::assertNotPushed(CharacterInfoJob::class);
    }


}
