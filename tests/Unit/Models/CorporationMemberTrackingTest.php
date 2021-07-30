<?php

namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Application;
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

        Queue::fake();

        $station = Station::factory()->create();

        Location::factory()->create([
            'location_id' => $station->station_id,
            'locatable_id' => $station->station_id,
            'locatable_type' => Station::class,
        ]);

        $this->tracking = CorporationMemberTracking::factory()->create([
            'location_id' => $station->station_id,
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

    public function it_uses_model_events_on_deletion()
    {
        Event::fake();

        Event::assertNotDispatched('eloquent.deleted: ' . CorporationMemberTracking::class);

        $this->tracking->delete();

        Event::assertDispatched('eloquent.deleted: ' . CorporationMemberTracking::class);
    }


}
