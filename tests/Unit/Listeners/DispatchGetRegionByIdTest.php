<?php

use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Listeners\DispatchGetRegionById;
use Seatplus\Eveapi\Models\Universe\Constellation;

beforeEach(function () {
    Queue::fake();
});

it('dispatches job when region is null', function () {
    $constellation = Constellation::factory()->noRegion()->make();

    $event = mock(UniverseConstellationCreated::class, fn ($mock) => $mock->constellation = $constellation);

    $listener = new DispatchGetRegionById;
    $listener->handle($event);

    Queue::assertPushedOn('default', ResolveUniverseRegionByRegionIdJob::class);
});

it('does not dispatch job when region already exists', function () {
    $event = mock(UniverseConstellationCreated::class, function (MockInterface $mock) {
        $mock->constellation = Constellation::factory()->make();
    })->makePartial();

    $listener = new DispatchGetRegionById;
    $listener->handle($event);

    Queue::assertNotPushed(ResolveUniverseRegionByRegionIdJob::class);
});
