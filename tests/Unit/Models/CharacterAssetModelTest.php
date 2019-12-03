<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\CharacterAssetUpdating;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
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

}