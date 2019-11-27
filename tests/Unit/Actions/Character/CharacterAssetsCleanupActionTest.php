<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Character;


use Seatplus\Eveapi\Actions\Character\CharacterAssetsCleanupAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetsCleanupActionTest extends TestCase
{
    /** @test */
    public function item_is_present_if_know(){

        $asset = factory(CharacterAsset::class)->create();

        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id
        ]);
    }

    /** @test */
    public function item_is_deleted_if_unknown(){

        $asset = factory(CharacterAsset::class)->create();

        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id
        ]);

        (new CharacterAssetsCleanupAction)->execute($asset->character_id, []);

        $this->assertDatabaseMissing('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id
        ]);
    }

    /** @test */
    public function only_unknown_item_is_deleted(){

        $assets = factory(CharacterAsset::class, 2)->create();

        foreach ($assets as $asset) {
            $this->assertDatabaseHas('character_assets', [
                'character_id' => $asset->character_id,
                'item_id' => $asset->item_id
            ]);
        }

        $first_element = $assets->first();
        $second_element = $assets->last();

        // Pretend to only know the first item
        (new CharacterAssetsCleanupAction)->execute($first_element->character_id, [$first_element->item_id]);

        $this->assertDatabaseHas('character_assets', [
            'character_id' => $first_element->character_id,
            'item_id' => $first_element->item_id
        ]);

        $this->assertDatabaseMissing('character_assets', [
            'character_id' => $second_element->character_id,
            'item_id' => $second_element->item_id
        ]);
    }


}
