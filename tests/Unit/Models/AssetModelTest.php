<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\AssetUpdating;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('creates an event upon updating', function () {
    $asset = Asset::factory()->create([
        'assetable_id' => 42,
    ]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
    ]);

    Event::fake();

    $character_asset = Asset::find($asset->item_id);
    $character_asset->assetable_id = 1337;
    $character_asset->save();

    Event::assertDispatched(AssetUpdating::class);
});

it('creates no event upon no update', function () {
    $asset = Asset::factory()->create([
        'assetable_id' => 42,
    ]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
    ]);

    Event::fake();

    $character_asset = Asset::find($asset->item_id);
    $character_asset->assetable_id = 42;
    $character_asset->save();

    Event::assertNotDispatched(AssetUpdating::class);
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
        'assetable_id' => $this->test_character->character_id,//CharacterInfo::factory(),
        'assetable_type' => CharacterInfo::class,
    ]);

    expect($test_asset->assetable)->toBeInstanceOf(CharacterInfo::class);
});

it('has scope affiliated where in', function () {
    $test_asset = Asset::factory()->create();

    $assets = Asset::query()->entityFilter([$test_asset->assetable_id])->first();

    expect($test_asset->item_id)->toEqual($assets->item_id);
});

it('has scope entityFilter', function () {
    $test_asset = Asset::factory()->create();

    $assets = Asset::query()->entityFilter($test_asset->assetable_id)->first();

    expect($test_asset->item_id)->toEqual($assets->item_id);
});

it('has scope search asset name', function () {
    $test_asset = Asset::factory()->withName()->create();

    $search_string = substr($test_asset->name, 0, 3);

    $assets = Asset::query()->search($search_string)->first();

    expect($assets)
        ->name->toBeString()->toBe($test_asset->name)
        ->item_id->toBeInt()->toBe($test_asset->item_id);
});

it('has scope search asset type', function () {
    $test_asset = Asset::factory()->withType()->create();

    $search_string = substr($test_asset->type->name, 0, 3);

    $assets = Asset::query()->search($search_string)->first();

    expect($assets)
        ->item_id->toBeInt()->toBe($test_asset->item_id);
});

it('has scope search asset content', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->withName()->create([
        'location_flag' => 'cargo',
    ]));

    $search_string = substr($test_asset->content->first()->name, 0, 3);

    $assets = Asset::query()
        ->Search($search_string)
        ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
        ->first();

    expect($test_asset->item_id)->toEqual($assets->item_id);
});

it('has scope search asset content content', function () {
    $test_asset = Asset::factory()->withName()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->create([
        'location_flag' => 'cargo',
    ]));

    $content = $test_asset->content->first();

    //Create Content Content
    $content->content()->save(Asset::factory()->withName()->withType()->create());

    $search_string = substr($content->content->first()->name, 0, 3);

    $assets = Asset::query()
        ->Search($search_string)
        ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
        ->first();

    expect($test_asset->item_id)
        ->toEqual($assets->item_id);

    // Search for type of content_content
    $search_string = substr($content->content->first()->type->name, 0, 3);

    expect($search_string)->toBeString()->toHaveLength(3);

    $assets = Asset::query()
        ->search($search_string)
        ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
        ->first();

    expect($assets)
        ->name->toBeString()->toBe($test_asset->name)
        ->item_id->toBeInt()->toBe($test_asset->item_id);
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

it('has in region scope', function () {
    expect(Asset::all())->toHaveCount(0);

    $test_asset = Event::fakeFor(fn () => Asset::factory()->create([
        'location_flag' => 'Hangar',
        'location_id' => Location::factory()->create([
            'locatable_type' => Station::class,
            'locatable_id' => Station::factory()->create([
                'system_id' => System::factory()->create([
                    'constellation_id' => Constellation::factory()->create([
                        'region_id' => Region::factory(),
                    ]),
                ]),
            ]),
        ]),
    ]));

    $region_id = $test_asset->location->locatable->system->region->region_id;

    expect(Asset::inRegion($region_id)->get())->toHaveCount(1);
    expect(Asset::inRegion($region_id + 1)->get())->toHaveCount(0);
});

it('has in system scope', function () {
    expect(Asset::all())->toHaveCount(0);

    $test_asset = Event::fakeFor(fn () => Asset::factory()->create([
        'location_flag' => 'Hangar',
        'location_id' => Location::factory()->create([
            'locatable_type' => Station::class,
            'locatable_id' => Station::factory()->create([
                'system_id' => System::factory()->create([
                    'constellation_id' => Constellation::factory()->create([
                        'region_id' => Region::factory(),
                    ]),
                ]),
            ]),
        ]),
    ]));

    $system_id = $test_asset->location->locatable->system->system_id;

    expect(Asset::inSystems($system_id)->get())->toHaveCount(1);
    expect(Asset::inSystems($system_id + 1)->get())->toHaveCount(0);
});

it('has in ofTypes, ofGroups and ofCategories scope', function () {
    expect(Asset::all())->toHaveCount(0);

    Event::fake();

    $type1 = Type::factory()->create([
        'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
    ]);

    $asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
        'type_id' => $type1,
    ]);

    $type2 = Type::factory()->create([
        'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
    ]);

    $content = Asset::factory()->create([
        'type_id' => $type2,
        'location_id' => $asset->item_id,
        'location_flag' => 'ShipHangar',
    ]);

    $type3 = Type::factory()->create([
        'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
    ]);

    $content_content = Asset::factory()->create([
        'type_id' => $type3,
        'location_id' => $content->item_id,
        'location_flag' => 'Hangar',
    ]);

    expect(Asset::query())
        ->count()->toBeInt()->toBe(3)
        ->ofTypes($type1->type_id)->get()->toHaveCount(1)
        ->ofTypes($type2->type_id)->where('location_flag', 'Hangar')->get()->toHaveCount(1)
        ->ofTypes($type3->type_id)->where('location_flag', 'Hangar')->get()->toHaveCount(1)
        ->ofGroups($type1->group_id)->get()->toHaveCount(1)
        ->ofGroups($type2->group_id)->where('location_flag', 'Hangar')->get()->toHaveCount(1)
        ->ofGroups($type3->group_id)->where('location_flag', 'Hangar')->get()->toHaveCount(1)
        ->ofCategories($type1->group->category_id)->get()->toHaveCount(1)
        ->ofCategories($type2->group->category_id)->where('location_flag', 'Hangar')->get()->toHaveCount(1)
        ->ofCategories($type3->group->category_id)->where('location_flag', 'Hangar')->get()->toHaveCount(1);
});
