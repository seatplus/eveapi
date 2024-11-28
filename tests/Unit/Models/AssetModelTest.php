<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

test('model has types', function () {
    $test_asset = Asset::factory()->withType()->create();

    $assets = Asset::has('type')->get();

    expect($assets->contains($test_asset))->toBeTrue();
});

test('model misses types', function () {
    $test_asset = Asset::factory()->create();

    $assets = Asset::has('type')->get();

    expect($assets->contains($test_asset))->toBeFalse();
});

test('model has location', function () {
    $test_asset = Asset::factory()->create();

    $test_asset->location()->save(Location::factory()->create());

    $assets = Asset::has('location')->get();

    expect($assets->contains($test_asset))->toBeTrue();
});

it('has scope assets location ids', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
        'location_type' => 'other',
    ]);

    $assets = Asset::query()->assetsLocationIds()->first();

    expect($test_asset->location_id)->toEqual($assets->location_id);
});

it('has assetable relationship', function () {
    $test_asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id, //CharacterInfo::factory(),
        'assetable_type' => CharacterInfo::class,
    ]);

    expect($test_asset->assetable)->toBeInstanceOf(CharacterInfo::class);
});

it('has content relationship', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->create([
        'location_flag' => 'cargo',
    ]));

    expect($test_asset->content->first())->toBeInstanceOf(Asset::class);
});

it('has container relationship', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->create([
        'location_flag' => 'cargo',
    ]));

    expect($test_asset->content->first()->container)->toBeInstanceOf(Asset::class);
});

it('has in scope', function (string $scope) {
    expect(Asset::all())->toHaveCount(0);

    Event::fake();

    $type = Type::factory()->create([
        'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
    ]);

    Asset::factory()->create([
        'location_flag' => 'Hangar',
        'type_id' => $type->type_id,
        'group_id' => $type->group->group_id,
        'category_id' => $type->group->category->category_id,
    ]);

    $query = Asset::query();

    match ($scope) {
        'ofTypes' => $query->filterByTypeIds($type->type_id),
        'ofGroups' => $query->filterByGroupIds($type->group->group_id),
        'ofCategories' => $query->filterByCategoryIds($type->group->category->category_id),
    };

    expect($query->get())->toHaveCount(1);
})->with(['ofTypes', 'ofGroups', 'ofCategories']);

it('excludes assets in asset safety', function () {
    $assetInSafety = Asset::factory()->create(['location_id' => Asset::ASSET_SAFETY]);
    $assetNotInSafety = Asset::factory()->create(['location_id' => 12345]);

    $result = Asset::withoutAssetSafety()->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->is($assetNotInSafety))->toBeTrue();
});

it('includes assets not in asset safety', function () {
    $assetNotInSafety1 = Asset::factory()->create(['location_id' => 12345]);
    $assetNotInSafety2 = Asset::factory()->create(['location_id' => 67890]);

    $result = Asset::withoutAssetSafety()->get();

    expect($result)->toHaveCount(2)
        ->and($result->contains($assetNotInSafety1))->toBeTrue()
        ->and($result->contains($assetNotInSafety2))->toBeTrue();
});
