<?php


namespace Seatplus\Eveapi\Tests\Integration;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class TypeLifeCycleTest extends TestCase
{

    /** @test */
    public function new_type_id_dispatches_group_job_if_group_is_not_present()
    {
        Queue::fake();

        $type = Type::factory()->create();

        Queue::assertPushedOn('high', ResolveUniverseGroupsByGroupIdJob::class);
    }

    /** @test */
    public function new_type_does_not_dispatches_group_job_if_group_is_present()
    {
        Queue::fake();

        $group = Group::factory()->create();

        $type = Type::factory()->create([
            'group_id' => $group->group_id
        ]);

        Queue::assertNotPushed(ResolveUniverseGroupsByGroupIdJob::class);
    }

}
