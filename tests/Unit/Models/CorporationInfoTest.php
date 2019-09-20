<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationInfoTest extends TestCase
{
    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $this->assertEquals(
            $this->test_character->corporation_id,
            $this->test_character->corporation->corporation_id
        );
    }

    /** @test */
    public function databaseRowIsCreated()
    {
        $this->assertDatabaseHas('corporation_infos', [
            'corporation_id' => $this->test_character->corporation_id
        ]);
    }

    /** @test */
    public function createCharacterCorporationReleation()
    {
        $character = factory(CharacterInfo::class)->make();

        $character->corporation()->associate(factory(CorporationInfo::class)->make());

        $this->assertEquals(
            $character->corporation_id,
            $character->corporation->corporation_id
        );

    }

    /** @test */
    public function createManyCharacterRelation()
    {
        $corporation = factory(CorporationInfo::class)->create();

        $corporation->characters()->createMany(
            factory(CharacterInfo::class, 3)->make()->toArray()
        );

        $this->assertEquals(3, $corporation->characters()->count());

    }


}