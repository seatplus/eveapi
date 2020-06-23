<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Tests\TestCase;

class AllianceInfoModelTest extends TestCase
{
    /** @test */
    public function it_has_morphable_sso_scope()
    {
        $alliance_info = factory(AllianceInfo::class)->create();

        $alliance_info->ssoScopes()->save(factory(SsoScopes::class)->make());

        $this->assertInstanceOf(SsoScopes::class, $alliance_info->refresh()->ssoScopes);
    }

    /** @test */
    public function it_has_character_affiliation()
    {
        $affiliation = $this->test_character->character_affiliation;

        if(!$affiliation->alliance_id) {
            $alliance = factory(AllianceInfo::class)->create();
            $affiliation->alliance_id = $alliance->alliance_id;
            $affiliation->save();
        }

        $this->assertNotNull($affiliation->alliance_id);

        $alliance = $affiliation->alliance;

        $this->assertInstanceOf(AllianceInfo::class, $alliance);

        $this->assertInstanceOf(CharacterInfo::class, $alliance->characters->first());

        $this->assertEquals($this->test_character->character_id, $alliance->characters->first()->character_id);

    }

}
