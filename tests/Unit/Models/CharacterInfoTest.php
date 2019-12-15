<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Faker\Factory;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
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
        $faker = Factory::create();

        $alliance_id = $faker->numberBetween(99000000,100000000);

        $affiliation = factory(CharacterAffiliation::class)->create([
            'alliance_id' => $alliance_id
        ]);

        $character = $affiliation->character()->save(factory(CharacterInfo::class)->create());

        $affiliation->alliance()->associate(factory(AllianceInfo::class)->create([
            'alliance_id' => $alliance_id
        ]));

        $this->assertInstanceOf(AllianceInfo::class, $character->alliance);
    }

    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $this->assertInstanceOf(CorporationInfo::class, $this->test_character->corporation);
    }

    public function it_has_corporation_id()
    {
        $this->assertTrue(true);
    }

}
