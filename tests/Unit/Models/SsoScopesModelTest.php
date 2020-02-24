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
        $alliance_info = factory(AllianceInfo::class)->create();

        $alliance_info->ssoScopes()->save(factory(SsoScopes::class)->make());

        $sso = factory(SsoScopes::class)->create([
            'morphable_type' => AllianceInfo::class,
            'morphable_id' => $alliance_info->alliance_id
        ]);

        $this->assertInstanceOf(AllianceInfo::class, $sso->refresh()->morphable);
    }

}
