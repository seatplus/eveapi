<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Tests\TestCase;

class SsoScopesModelTest extends TestCase
{
    /** @test */
    public function one_can_set_an_alliance_relationship_from_sso_scope_model()
    {
        $alliance_info = AllianceInfo::factory()->create();

        $alliance_info->ssoScopes()->save(SsoScopes::factory()->make());

        $sso = SsoScopes::factory()->create([
            'morphable_type' => AllianceInfo::class,
            'morphable_id' => $alliance_info->alliance_id
        ]);

        $this->assertInstanceOf(AllianceInfo::class, $sso->refresh()->morphable);
    }

    /** @test */
    public function has_global_scope()
    {

        $sso = SsoScopes::updateOrCreate(['type' => 'global'], ['selected_scopes' => collect()->toJson()]);

        $this->assertEquals($sso->type, SsoScopes::global()->first()->type);
    }

}
