<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

test('new type id dispatches group job if group is not present', function () {
    Queue::fake();

    $type = Type::factory()->create();

    Queue::assertPushedOn('high', ResolveUniverseGroupByIdJob::class);
});

test('new type does not dispatches group job if group is present', function () {
    Queue::fake();

    $group = Group::factory()->create();

    $type = Type::factory()->create([
        'group_id' => $group->group_id,
    ]);

    Queue::assertNotPushed(ResolveUniverseGroupByIdJob::class);
});

it('self-heals asset enrichment when a type arrives into an existing group and category', function () {
    // The group + category are already resolved (e.g. a new item in a known group).
    $category = Category::factory()->create();
    $group = Group::factory()->create(['category_id' => $category->category_id]);

    // An asset created before its type resolved. Because the group and category
    // already exist, resolving the type creates neither - so no Group/Category
    // observer fires and only TypeObserver can backfill this asset.
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => 313131,
        'group_id' => null,
    ]);

    expect($asset->fresh()->group_id)->toBeNull();

    Type::factory()->create([
        'type_id' => 313131,
        'group_id' => $group->group_id,
    ]);

    expect($asset->fresh())
        ->group_id->toBe($group->group_id)
        ->category_id->toBe($category->category_id)
        ->type_name_normalized->not->toBeNull();
});
