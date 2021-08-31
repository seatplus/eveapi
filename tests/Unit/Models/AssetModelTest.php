<?php


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\AssetUpdating;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

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

    $this->assertTrue($assets->contains($test_asset));
});

test('model misses types', function () {
    $test_asset = Asset::factory()->create();

    $assets = Asset::has('type')->get();

    $this->assertFalse($assets->contains($test_asset));
});

test('model has location', function () {
    $test_asset = Asset::factory()->create();

    $test_asset->location()->save(Location::factory()->create());

    $assets = Asset::has('location')->get();

    $this->assertTrue($assets->contains($test_asset));
});

it('has scope assets location ids', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
        'location_type' => 'other',
    ]);

    $assets = Asset::query()->assetsLocationIds()->first();

    $this->assertEquals($assets->location_id, $test_asset->location_id);
});

it('has scope affiliated without request', function () {
    $test_asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,//CharacterInfo::factory(),
        'assetable_type' => CharacterInfo::class,
    ]);
    ;

    $characters_mock = \Mockery::mock(Collection::class);
    $characters_mock->shouldReceive('pluck')
        ->once()
        ->andReturn(collect($test_asset->assetable_id));

    $user_mock = \Mockery::mock('Seatplus\Auth\Models\User');
    $user_mock->characters = $characters_mock;
    $user_mock->characters->shouldReceive('pluck')->andReturn($this->test_character->character_id);

    Auth::shouldReceive('user')
        ->once()
        ->andReturn($user_mock);

    //dd(Asset::all(), $this->test_character->character_id, auth()->user()->characters->pluck('character_id')->toArray());

    $asset = Asset::query()->Affiliated([$this->test_character->character_id])->first();
    ;

    $this->assertEquals($asset->item_id, $test_asset->item_id);
});

it('has scope affiliated with request', function () {
    $test_asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,//CharacterInfo::factory(),
        'assetable_type' => CharacterInfo::class,
    ]);

    $asset = Asset::query()->Affiliated([$this->test_character->character_id], [$this->test_character->character_id])->first();
    ;

    $this->assertEquals($asset->item_id, $test_asset->item_id);
});

it('has assetable relationship', function () {
    $test_asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,//CharacterInfo::factory(),
        'assetable_type' => CharacterInfo::class,
    ]);

    $this->assertInstanceOf(CharacterInfo::class, $test_asset->assetable);
});

it('has scope affiliated where in', function () {
    $test_asset = Asset::factory()->create();

    $assets = Asset::query()->entityFilter([$test_asset->assetable_id])->first();

    $this->assertEquals($assets->item_id, $test_asset->item_id);
});

it('has scope affiliated where', function () {
    $test_asset = Asset::factory()->create();

    $assets = Asset::query()->entityFilter([$test_asset->assetable_id])->first();

    $this->assertEquals($assets->item_id, $test_asset->item_id);
});

it('has scope search asset name', function () {
    $test_asset = Asset::factory()->withName()->create();

    $assets = Asset::query()->Search($test_asset->name)->first();

    $this->assertEquals($assets->item_id, $test_asset->item_id);
});

it('has scope search asset type', function () {
    $test_asset = Asset::factory()->withName()->create();

    $assets = Asset::query()->search($test_asset->name)->first();

    $this->assertEquals($assets->item_id, $test_asset->item_id);
});

it('has scope search asset content', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->withName()->create([
        'location_flag' => 'cargo',
    ]));

    $assets = Asset::query()
        ->Search($test_asset->content->first()->name)
        ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
        ->first();

    $this->assertEquals($assets->item_id, $test_asset->item_id);
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
    $content->content()->save(Asset::factory()->withName()->create());

    $content_content = $content->content->first();

    $assets = Asset::query()
        ->Search($content_content->name)
        ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
        ->first();

    $this->assertEquals($assets->item_id, $test_asset->item_id);
});

it('has content relationship', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->create([
        'location_flag' => 'cargo',
    ]));

    $this->assertInstanceOf(Asset::class, $test_asset->content->first());
});

it('has container relationship', function () {
    $test_asset = Asset::factory()->create([
        'location_flag' => 'Hangar',
    ]);

    //Create Content
    $test_asset->content()->save(Asset::factory()->create([
        'location_flag' => 'cargo',
    ]));

    $this->assertInstanceOf(Asset::class, $test_asset->content->first()->container);
});

it('has in region scope', function () {
    $this->assertCount(0, Asset::all());

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

    $this->assertCount(1, Asset::inRegion($region_id)->get());
    $this->assertCount(0, Asset::inRegion($region_id + 1)->get());
});

it('has in system scope', function () {
    $this->assertCount(0, Asset::all());

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

    $this->assertCount(1, Asset::inSystems($system_id)->get());
    $this->assertCount(0, Asset::inSystems($system_id + 1)->get());
});
