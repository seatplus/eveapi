<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationInfoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

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
            'corporation_id' => $this->test_character->corporation_id,
        ]);
    }

    /** @test */
    public function createCharacterCorporationReleation()
    {
        $character = CharacterInfo::factory()->make();

        $character_affiliation = $character->character_affiliation()->save(CharacterAffiliation::factory()->make());

        $character_affiliation->corporation()->associate(CorporationInfo::factory()->create([
            'corporation_id' => $character_affiliation->corporation_id,
        ]));

        $this->assertEquals(
            $character->corporation_id,
            $character->corporation->corporation_id
        );
    }

    /** @test */
    public function createManyCharacterRelation()
    {
        $corporation = CorporationInfo::factory()->create();

        $characters = CharacterAffiliation::factory()->count(3)->create([
            'corporation_id' => $corporation->corporation_id,
            'alliance_id' => $corporation->alliance_id,
        ]);

        foreach ($characters as $character) {
            $character->character()->save(CharacterInfo::factory()->create());
        }

        $this->assertEquals(3, $corporation->characters()->count());
    }

    /** @test */
    public function it_has_morphable_sso_scope()
    {
        $corporation_info = CorporationInfo::factory()
            ->hasSsoScopes()
            ->create();

        //$corporation_info->ssoScopes()->save(SsoScopes::factory()->make());

        $this->assertInstanceOf(SsoScopes::class, $corporation_info->refresh()->ssoScopes);
    }

    /** @test */
    public function it_has_recruits_relationship()
    {
        $corporation_info = CorporationInfo::factory()->create();

        $app = Application::factory()->count(5)->create([
            'corporation_id' => $corporation_info->corporation_id,
        ]);

        foreach ($corporation_info->refresh()->candidates as $candidate) {
            $this->assertInstanceOf(Application::class, $candidate);
        }

        $this->assertEquals(5, $corporation_info->refresh()->candidates->count());
    }

    /** @test */
    public function it_has_alliance_relationship()
    {
        $corporation_info = CorporationInfo::factory()->create([
            'alliance_id' => AllianceInfo::factory(),
        ]);

        $this->assertInstanceOf(AllianceInfo::class, $corporation_info->alliance);
    }

    /** @test */
    public function it_has_members_relationship()
    {
        $member_tracking = CorporationMemberTracking::factory()->create([
            'character_id' => $this->test_character->character_id,
            'corporation_id' => $this->test_character->corporation->corporation_id,
        ]);

        $this->assertInstanceOf(CorporationMemberTracking::class, $this->test_character->refresh()->corporation->members->first());
    }

    /** @test */
    public function it_has_wallets_relationship()
    {
        $balance = Balance::factory()->withDivision()->create([
            'balanceable_id' => $this->test_character->corporation->corporation_id,
            'balanceable_type' => CorporationInfo::class,
        ]);

        $this->assertInstanceOf(Balance::class, $this->test_character->corporation->refresh()->wallets->first());
    }
}
