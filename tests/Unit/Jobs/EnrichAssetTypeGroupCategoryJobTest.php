<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

it('returns early if batch is cancelled', function () {
    // An asset that would otherwise be enriched: the whole type->group->category chain resolves.
    $category = Event::fakeFor(fn () => Category::factory()->create());
    $group = Event::fakeFor(fn () => Group::factory()->create(['category_id' => $category->category_id]));
    $type = Event::fakeFor(fn () => Type::factory()->create(['group_id' => $group->group_id]));
    $asset = Event::fakeFor(fn () => Asset::factory()->create(['type_id' => $type->type_id]));

    [$job, $batch] = new EnrichAssetTypeGroupCategoryJob()->withFakeBatch();
    $batch->cancel();

    $job->handle();

    expect($asset->refresh())
        ->type_name_normalized->toBeNull()
        ->group_id->toBeNull()
        ->category_id->toBeNull();
});
