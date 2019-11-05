<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoTest extends TestCase
{
    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $this->assertEquals(
            $this->test_character->alliance_id,
            $this->test_character->alliance->alliance_id
        );
    }

}