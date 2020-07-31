<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\SsoScopes;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationMemberTrackingTest extends TestCase
{

    private CorporationMemberTracking $tracking;

    protected function setUp(): void
    {

        parent::setUp();

        $this->tracking = factory(CorporationMemberTracking::class)->create();

        factory(Station::class)->create([
            'station_id' => $this->tracking->location_id
        ]);

        factory(Location::class)->create([
            'location_id' => $this->tracking->location_id,
            'locatable_id' => $this->tracking->location_id,
            'locatable_type' => Station::class,
        ]);

    }

    /** @test */
    public function it_has_corporation_relation()
    {
        $this->assertInstanceOf(CorporationInfo::class, $this->tracking->corporation);
    }

    /** @test */
    public function it_has_location_relation()
    {
        $this->assertInstanceOf(Location::class, $this->tracking->location);
    }

    /** @test */
    public function it_has_character_relation()
    {
        $this->assertInstanceOf(CharacterInfo::class, $this->tracking->character);
    }


}
