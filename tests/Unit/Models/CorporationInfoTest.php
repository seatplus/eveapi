<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\SsoScopes;
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

        $character_affiliation = $character->character_affiliation()->save(factory(CharacterAffiliation::class)->make());

        $character_affiliation->corporation()->associate(factory(CorporationInfo::class)->create([
            'corporation_id' => $character_affiliation->corporation_id
        ]));

        $this->assertEquals(
            $character->corporation_id,
            $character->corporation->corporation_id
        );

    }

    /** @test */
    public function createManyCharacterRelation()
    {
        $corporation = factory(CorporationInfo::class)->create();

        $characters = factory(CharacterAffiliation::class, 3)->create([
            'corporation_id' => $corporation->corporation_id,
            'alliance_id' => $corporation->alliance_id
        ]);

        foreach ($characters as $character)
            $character->character()->save(factory(CharacterInfo::class)->create());

        $this->assertEquals(3, $corporation->characters()->count());
    }

    /** @test */
    public function it_has_morphable_sso_scope()
    {
        $corporation_info = factory(CorporationInfo::class)->create();

        $corporation_info->ssoScopes()->save(factory(SsoScopes::class)->make());

        $this->assertInstanceOf(SsoScopes::class, $corporation_info->refresh()->ssoScopes);
    }


}
