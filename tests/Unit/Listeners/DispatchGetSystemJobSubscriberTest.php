<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Listeners\DispatchGetSystemJobSubscriber;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;

beforeEach(function () {
    Queue::fake();
});

it('dispatches job when system does not exist', function () {
    $station = Station::factory()->noSystem()->make();

    $event = new UniverseStationCreated($station);

    $listener = new DispatchGetSystemJobSubscriber;
    $listener->handleUniverseStationCreated($event);

    Queue::assertPushedOn('default', ResolveUniverseSystemBySystemIdJob::class);
});

it('does not dispatch job when system already exists', function () {
    $system = System::factory()->create();
    $station = Station::factory()->make(['system_id' => $system->system_id]);

    $event = new UniverseStationCreated($station);

    $listener = new DispatchGetSystemJobSubscriber;
    $listener->handleUniverseStationCreated($event);

    Queue::assertNotPushed(ResolveUniverseSystemBySystemIdJob::class);
});
