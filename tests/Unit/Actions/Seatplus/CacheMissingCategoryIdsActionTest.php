<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;

use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCategoryIdsAction;
use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCharacterTypeIdsAction;
use Seatplus\Eveapi\Actions\Seatplus\CacheMissingGroupIdsAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Groups;
use Seatplus\Eveapi\Models\Universe\Types;
use Seatplus\Eveapi\Tests\TestCase;

class CacheMissingCategoryIdsActionTest extends TestCase
{
    /** @test */
    public function it_gets_missing_category_realtionship()
    {
        $groups = factory(Groups::class,5)->create();

        foreach ($groups as $group)
            //Assert that test character is now created
            $this->assertDatabaseHas('universe_groups', [
                'group_id' => $group->group_id
            ]);

        $cached_type_ids = (new CacheMissingCategoryIdsAction)->execute();

        foreach ($cached_type_ids as $cached_type_id)
            $this->assertTrue(in_array($cached_type_id, $groups->pluck('category_id')->unique()->values()->toArray()));
    }

}
