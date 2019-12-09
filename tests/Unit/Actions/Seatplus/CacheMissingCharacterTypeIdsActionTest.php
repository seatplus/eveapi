<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;

use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCharacterTypeIdsAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Tests\TestCase;

class CacheMissingCharacterTypeIdsActionTest extends TestCase
{
    /** @test */
    public function it_gets_missing_type_realtionships()
    {
        $assets = factory(CharacterAsset::class,5)->create();

        foreach ($assets as $asset)
            //Assert that test character is now created
            $this->assertDatabaseHas('character_assets', [
                'item_id' => $asset->item_id
            ]);

        $cached_type_ids = (new CacheMissingCharacterTypeIdsAction)->execute();

        $this->assertEquals($asset->pluck('type_id')->unique()->values(), $cached_type_ids);
    }

}
