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
        'assetable_id' => $this->test_character->character_id, //CharacterInfo::factory(),
        'assetable_type' => CharacterInfo::class,
    ]);

    expect($test_asset->assetable)->toBeInstanceOf(CharacterInfo::class);
});

it('has scope search asset name', function () {
    $test_asset = Asset::factory()->withName()->create();

    $search_string = substr($test_asset->name, 0, 3);

    $assets = Asset::query()->search($search_string)->first();

    expect($assets)
        ->name->toBeString()->toBe($test_asset->name)
        ->item_id->toBeInt()->toBe($test_asset->item_id);
});

it('has asset search scope by', function ($type) {
    $test_asset = Asset::factory()->create([
        'type_id' => Type::factory()->create([
            'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
        ]),
    ]);

    $name = match ($type) {
        'name' => $test_asset->name,
        'type' => $test_asset->type->name,
        'group' => $test_asset->type->group->name
    };

    $search_string = substr($name, 0, 3);

    $assets = Asset::query()->search($search_string)->first();

    expect($assets)
        ->item_id->toBeInt()->toBe($test_asset->item_id);
})->with(['name', 'type', 'group']);

it('has withRecursiveContent scope for Level', function ($level) {
    $test_asset = Asset::factory()->withName()->create();

    //Create Content
    $test_asset->content()->save(Asset::factory()->withName()->create([
        'location_flag' => 'cargo',
    ]));

    //Create Content Content
    $test_asset->content->first()->content()->save(Asset::factory()->withName()->withType()->create());

    expect(Asset::query()->count())->toBe(3);

    $name = match ($level) {
        0 => $test_asset->name,
        1 => $test_asset->content->first()->name,
        2 => $test_asset->content->first()->content->first()->name,
    };

    $search_string = substr($name, 0, 3);

    $assets = Asset::query()
        ->Search($search_string)
        ->withRecursiveContent()
        ->get();

    expect($assets)->toHaveCount($level + 1);
})->with([0, 1, 2]);

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

it('has in scope', function (string $scope) {
    expect(Asset::all())->toHaveCount(0);

    Event::fake();

    $type = Type::factory()->create([
        'group_id' => Group::factory()->create(['category_id' => Category::factory()]),
    ]);

    Asset::factory()->create([
        'location_flag' => 'Hangar',
        'type_id' => $type,
    ]);

    $query = Asset::query();

    match ($scope) {
        'ofTypes' => $query->ofTypes($type->type_id),
        'ofGroups' => $query->ofGroups($type->group->group_id),
        'ofCategories' => $query->ofCategories($type->group->category->category_id),
    };

    expect($query->get())->toHaveCount(1);
})->with(['ofTypes', 'ofGroups', 'ofCategories']);
