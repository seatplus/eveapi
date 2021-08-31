<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
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
});

it('has corporation relation', function () {
    $this->assertInstanceOf(CorporationInfo::class, $this->tracking->corporation);
});

it('has location relation', function () {
    $this->assertInstanceOf(Location::class, $this->tracking->location);
});

it('has character relation', function () {
    $this->assertInstanceOf(CharacterInfo::class, $this->tracking->character);
});

// Helpers
function it_uses_model_events_on_deletion()
{
    Event::fake();

    Event::assertNotDispatched('eloquent.deleted: ' . CorporationMemberTracking::class);

    $this->tracking->delete();

    Event::assertDispatched('eloquent.deleted: ' . CorporationMemberTracking::class);
}
