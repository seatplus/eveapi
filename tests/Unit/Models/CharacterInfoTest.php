<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoTest extends TestCase
{
    /** @test */
    public function characterHasRefreshTokenRelationTest()
    {

        $this->assertInstanceOf(RefreshToken::class, $this->test_character->refresh_token);
    }

    /** @test */
    public function characterHasAllianceRelationTest()
    {

        $this->assertEquals(
            $this->test_character->alliance_id,
            $this->test_character->alliance->alliance_id
        );

        $this->assertInstanceOf(AllianceInfo::class, $this->test_character->alliance);
    }

    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $this->assertInstanceOf(CorporationInfo::class, $this->test_character->corporation);
    }

}
