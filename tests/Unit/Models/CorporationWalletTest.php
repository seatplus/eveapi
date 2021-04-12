<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Corporation\CorporationWallet;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationWalletTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function itHasCorporationReleationship()
    {
        $wallet = CorporationWallet::factory()->create([
            'corporation_id' => CorporationInfo::factory()->create()
        ]);

        $this->assertNotNull($wallet->refresh()->corporation);
    }


}
