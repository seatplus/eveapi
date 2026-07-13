<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('self-heals asset enrichment when the missing category lands', function () {
    // an asset whose type->group->category chain is not yet complete
    $type = Type::factory()->create(['group_id' => 88888]);
    Group::factory()->create([
        'group_id' => 88888,
        'category_id' => 77777,
    ]);
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'group_id' => null,
    ]);

    expect($asset->fresh()->group_id)->toBeNull();

    // SDE data lands late: the category completes the chain
    Category::factory()->create(['category_id' => 77777]);

    // the denormalized columns are backfilled without waiting for the next batch
    expect($asset->fresh())
        ->group_id->toBe(88888)
        ->category_id->toBe(77777)
        ->type_name_normalized->not->toBeNull();
});

it('does not enrich when no asset is waiting for the landing category', function () {
    // an unrelated asset that is already enriched
    $category = Category::factory()->create(['category_id' => 55555]);
    $type = Type::factory()->create(['group_id' => 66666]);
    Group::factory()->create([
        'group_id' => 66666,
        'category_id' => 55555,
    ]);
    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'group_id' => 66666,
        'category_id' => 55555,
    ]);

    // a different category lands, but no asset is missing enrichment
    Category::factory()->create(['category_id' => 77777]);

    expect($asset->fresh()->group_id)->toBe(66666);
});
