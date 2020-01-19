<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Seatplus\Auth\Models\User;
use Seatplus\Eveapi\Events\CharacterAssetUpdating;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetModelTest extends TestCase
{

    /** @test */
    public function it_creates_an_event_upon_updating()
    {
        $asset = factory(CharacterAsset::class)->create([
            'character_id' => 42
        ]);

        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id
        ]);

        Event::fake();

        $character_asset = CharacterAsset::find($asset->item_id);
        $character_asset->character_id = 1337;
        $character_asset->save();

        Event::assertDispatched(CharacterAssetUpdating::class);
    }

    /** @test */
    public function it_creates_no_event_upon_no_update()
    {
        $asset = factory(CharacterAsset::class)->create([
            'character_id' => 42
        ]);

        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id
        ]);

        Event::fake();

        $character_asset = CharacterAsset::find($asset->item_id);
        $character_asset->character_id = 42;
        $character_asset->save();

        Event::assertNotDispatched(CharacterAssetUpdating::class);
    }

    /** @test */
    public function model_has_types()
    {
        $test_asset = factory(CharacterAsset::class)->create();

        $assets = CharacterAsset::has('type')->get();

        $this->assertTrue($assets->contains($test_asset));
    }

    /** @test */
    public function model_misses_types()
    {
        $test_asset = factory(CharacterAsset::class)->state('withoutType')->create();

        $assets = CharacterAsset::has('type')->get();

        $this->assertFalse($assets->contains($test_asset));
    }

    /** @test */
    public function model_has_location()
    {
        $test_asset = factory(CharacterAsset::class)->create();

        $test_asset->location()->save(factory(Location::class)->create());

        $assets = CharacterAsset::has('location')->get();

        $this->assertTrue($assets->contains($test_asset));
    }

    /** @test */
    public function it_has_scopeAssetsLocationIds()
    {
        $test_asset = factory(CharacterAsset::class)->create([
            'location_flag' => 'Hangar',
            'location_type' => 'other'
        ]);

        $assets = CharacterAsset::query()->assetsLocationIds()->first();

        $this->assertEquals($assets->location_id, $test_asset->location_id);
    }

    /** @test */
    public function it_has_owner_relationship()
    {
        $test_asset = factory(CharacterAsset::class)->create();

        $this->assertInstanceOf(CharacterInfo::class, $test_asset->owner);
    }

    /** @test */
    public function it_has_scopeAffiliated_whereIn()
    {
        $test_asset = factory(CharacterAsset::class)->create();

        $user_mock = \Mockery::mock(User::class);

        $user_mock->shouldReceive('getAffiliatedCharacterIdsByPermission')
            ->once()
            ->andReturn([$test_asset->character_id]);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($user_mock);

        $assets = CharacterAsset::query()->Affiliated()->first();

        $this->assertEquals($assets->item_id, $test_asset->item_id);
    }

    /** @test */
    public function it_has_scopeAffiliated_where()
    {
        $test_asset = factory(CharacterAsset::class)->create();

        $assets = CharacterAsset::query()->Affiliated($test_asset->character_id)->first();

        $this->assertEquals($assets->item_id, $test_asset->item_id);
    }

    /** @test */
    public function it_has_scopeSearch_asset_name()
    {
        $test_asset = factory(CharacterAsset::class)->state('withName')->create();

        $assets = CharacterAsset::query()->Search($test_asset->name)->first();

        $this->assertEquals($assets->item_id, $test_asset->item_id);
    }

    /** @test */
    public function it_has_scopeSearch_asset_type()
    {
        $test_asset = factory(CharacterAsset::class)->state('withName')->create();

        $assets = CharacterAsset::query()->search($test_asset->name)->first();

        $this->assertEquals($assets->item_id, $test_asset->item_id);
    }

    /** @test */
    public function it_has_scopeSearch_asset_content()
    {
        $test_asset = factory(CharacterAsset::class)->create([
            'location_flag' => 'Hangar'
        ]);

        //Create Content
        $test_asset->content()->save(factory(CharacterAsset::class)->state('withName')->create([
            'location_flag' => 'cargo'
        ]));

        $assets = CharacterAsset::query()
            ->Search($test_asset->content->first()->name)
            ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
            ->first();

        $this->assertEquals($assets->item_id, $test_asset->item_id);
    }

    /** @test */
    public function it_has_scopeSearch_asset_content_content()
    {
        $test_asset = factory(CharacterAsset::class)->create([
            'location_flag' => 'Hangar'
        ]);

        //Create Content
        $test_asset->content()->save(factory(CharacterAsset::class)->create([
            'location_flag' => 'cargo'
        ]));

        $content = $test_asset->content->first();

            //Create Content Content
        $content->content()->save(factory(CharacterAsset::class)->state('withName')->create());

        $content_content = $content->content->first();

        $assets = CharacterAsset::query()
            ->Search($content_content->name)
            ->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
            ->first();

        $this->assertEquals($assets->item_id, $test_asset->item_id);
    }

    /** @test */
    public function it_has_content_relationship()
    {
        $test_asset = factory(CharacterAsset::class)->create([
            'location_flag' => 'Hangar'
        ]);

        //Create Content
        $test_asset->content()->save(factory(CharacterAsset::class)->create([
            'location_flag' => 'cargo'
        ]));

        $this->assertInstanceOf(CharacterAsset::class, $test_asset->content->first());
    }

    /** @test */
    public function it_has_container_relationship()
    {
        $test_asset = factory(CharacterAsset::class)->create([
            'location_flag' => 'Hangar'
        ]);

        //Create Content
        $test_asset->content()->save(factory(CharacterAsset::class)->create([
            'location_flag' => 'cargo'
        ]));

        $this->assertInstanceOf(CharacterAsset::class, $test_asset->content->first()->container);
    }


}
