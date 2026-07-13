<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

test('new group id dispatches category job if group is not present', function () {
    Queue::fake();

    $group = Group::factory()->create();

    Queue::assertPushedOn('high', ResolveUniverseCategoryByIdJob::class);
});

test('new group does not dispatches group job if category is present', function () {
    Queue::fake();

    $category = Category::factory()->create();

    $group = Group::factory()->create([
        'category_id' => $category->category_id,
    ]);

    Queue::assertNotPushed(ResolveUniverseCategoryByIdJob::class);
});

it('dispatches assets name job', function () {
    $type = Type::factory()->create();

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    Queue::fake();

    $group = Group::factory()->create([
        'group_id' => $type->group_id,
        'category_id' => 6,
    ]);

    Queue::assertPushedOn('high', CharacterAssetsNameJob::class);
});

it('self-heals asset enrichment when a group with a resolvable category lands', function () {
    Queue::fake();

    // an asset whose type->group has not been resolved yet
    $category = Category::factory()->create();
    $type = Type::factory()->create(['group_id' => 99999]);
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'group_id' => null,
    ]);

    expect($asset->fresh()->group_id)->toBeNull();

    // SDE data lands late: the group (with a resolvable category) arrives
    Group::factory()->create([
        'group_id' => 99999,
        'category_id' => $category->category_id,
    ]);

    // the denormalized columns are backfilled without waiting for the next batch
    expect($asset->fresh())
        ->group_id->toBe(99999)
        ->category_id->toBe($category->category_id)
        ->type_name_normalized->not->toBeNull();
});

it('does not enrich when the landing group has no resolvable category', function () {
    Queue::fake();

    $type = Type::factory()->create(['group_id' => 99999]);
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'group_id' => null,
    ]);

    // group lands but its category is not resolvable yet
    Group::factory()->create([
        'group_id' => 99999,
        'category_id' => 12345,
    ]);

    expect($asset->fresh()->group_id)->toBeNull();
});
