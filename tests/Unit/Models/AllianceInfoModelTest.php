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
        $alliance_info = AllianceInfo::factory()->create();

        $alliance_info->ssoScopes()->save(SsoScopes::factory()->make());

        $this->assertInstanceOf(SsoScopes::class, $alliance_info->refresh()->ssoScopes);
    }

    /** @test */
    public function it_has_character_affiliation()
    {
        $affiliation = $this->test_character->character_affiliation;

        if(!$affiliation->alliance_id) {
            $alliance = AllianceInfo::factory()->create();
            $affiliation->alliance_id = $alliance->alliance_id;
            $affiliation->save();
        }

        $this->assertNotNull($affiliation->alliance_id);

        $alliance = $affiliation->alliance;

        $this->assertInstanceOf(AllianceInfo::class, $alliance);

        $this->assertInstanceOf(CharacterInfo::class, $alliance->characters->first());

        $this->assertEquals($this->test_character->character_id, $alliance->characters->first()->character_id);

    }

    /** @test */
    public function it_has_corporations_relation()
    {
        $character_affiliation = $this->test_character->character_affiliation;
        $character_affiliation->alliance_id = AllianceInfo::factory()->create()->alliance_id;
        $character_affiliation->save();

        $corporation = $this->test_character->corporation;
        $corporation->alliance_id  = $character_affiliation->alliance_id;
        $corporation->save();

        $this->assertInstanceOf(CorporationInfo::class, $this->test_character->alliance->corporations->first());
    }

}
