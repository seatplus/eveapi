<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

it('does not enrich asset if category is missing', function () {
    $group = Event::fakeFor(fn () => Group::factory()->create());
    $type = Event::fakeFor(fn () => Type::factory()->create([
        'group_id' => $group->group_id,
    ]));
    Event::fakeFor(fn () => Asset::factory()->create([
        'type_id' => $type->type_id,
    ]));

    $mock = Mockery::mock(EnrichAssetTypeGroupCategoryJob::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();

    $mock->handle();

    // assert
    $assets = Asset::all();

    expect($assets->count())->toBe(1);

    expect($assets->first())
        ->type->toBeInstanceOf(Type::class)
        ->type->group->toBeInstanceOf(Group::class)
        ->type->group->category->toBeNull()
        ->type_name_normalized->toBeNull();
});

it('enriches asset if category is present', function () {

    // Prepare data
    $category = Event::fakeFor(fn () => Category::factory()->create());
    $group = Event::fakeFor(fn () => Group::factory()->create([
        'category_id' => $category->category_id,
    ]));
    $type = Event::fakeFor(fn () => Type::factory()->create([
        'group_id' => $group->group_id,
    ]));

    Event::fakeFor(fn () => Asset::factory()->create([
        'type_id' => $type->type_id,
    ]));

    // run the job
    $mock = Mockery::mock(EnrichAssetTypeGroupCategoryJob::class)->makePartial();

    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();

    $mock->handle();

    // assert
    $assets = Asset::all();

    expect($assets->count())->toBe(1);

    expect($assets->first())
        ->type->toBeInstanceOf(Type::class)
        ->type->group->toBeInstanceOf(Group::class)
        ->type->group->category->toBeInstanceOf(Category::class)
        ->type_name_normalized->not()->toBeNull();
});

it('heals a partially enriched asset whose group_id is set but name columns are null', function () {
    // A legacy row: an older code path populated group_id/category_id but never
    // filled the *_name_normalized columns. Keying enrichment on group_id alone
    // would skip it forever; needsUniverseEnrichment() must still pick it up.
    $category = Event::fakeFor(fn () => Category::factory()->create());
    $group = Event::fakeFor(fn () => Group::factory()->create([
        'category_id' => $category->category_id,
    ]));
    $type = Event::fakeFor(fn () => Type::factory()->create([
        'group_id' => $group->group_id,
    ]));

    Event::fakeFor(fn () => Asset::factory()->create([
        'type_id' => $type->type_id,
        'group_id' => $group->group_id,
        'category_id' => $category->category_id,
        'type_name_normalized' => null,
        'group_name_normalized' => null,
        'category_name_normalized' => null,
    ]));

    $mock = Mockery::mock(EnrichAssetTypeGroupCategoryJob::class)->makePartial();
    $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
    $mock->handle();

    expect(Asset::first())
        ->type_name_normalized->not()->toBeNull()
        ->group_name_normalized->not()->toBeNull()
        ->category_name_normalized->not()->toBeNull();
});
