<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

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
    expect($this->tracking->corporation)->toBeInstanceOf(CorporationInfo::class);
});

it('has location relation', function () {
    expect($this->tracking->location)->toBeInstanceOf(Location::class);
});

it('has character relation', function () {
    expect($this->tracking->character)->toBeInstanceOf(CharacterInfo::class);
});

// Helpers
function it_uses_model_events_on_deletion()
{
    Event::fake();

    Event::assertNotDispatched('eloquent.deleted: '.CorporationMemberTracking::class);

    $this->tracking->delete();

    Event::assertDispatched('eloquent.deleted: '.CorporationMemberTracking::class);
}
