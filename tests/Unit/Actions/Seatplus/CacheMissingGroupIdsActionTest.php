<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;

use Seatplus\Eveapi\Actions\Seatplus\CacheMissingCharacterTypeIdsAction;
use Seatplus\Eveapi\Actions\Seatplus\CacheMissingGroupIdsAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class CacheMissingGroupIdsActionTest extends TestCase
{
    /** @test */
    public function it_gets_missing_group_realtionship()
    {
        $types = factory(Type::class,5)->create();

        foreach ($types as $type)
            //Assert that test character is now created
            $this->assertDatabaseHas('universe_types', [
                'type_id' => $type->type_id
            ]);

        $cached_type_ids = (new CacheMissingGroupIdsAction)->execute();

        foreach ($cached_type_ids as $cached_type_id)
            $this->assertTrue(in_array($cached_type_id, $types->pluck('group_id')->unique()->values()->toArray()));
    }

}
