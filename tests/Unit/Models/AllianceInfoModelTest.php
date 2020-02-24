<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
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

}
