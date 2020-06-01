<?php


namespace Seatplus\Eveapi\Tests\Integration;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class GroupLifecycleTest extends TestCase
{

    /** @test */
    public function new_group_id_dispatches_category_job_if_group_is_not_present()
    {
        Queue::fake();

        $group = factory(Group::class)->create();

        Queue::assertPushedOn('high', ResolveUniverseCategoriesByCategoryIdJob::class);
    }

    /** @test */
    public function new_group_does_not_dispatches_group_job_if_category_is_present()
    {
        Queue::fake();

        $category = factory(Category::class)->create();

        $group = factory(Group::class)->create([
            'category_id' => $category->category_id
        ]);

        Queue::assertNotPushed(ResolveUniverseCategoriesByCategoryIdJob::class);
    }

}
