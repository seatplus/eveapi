<?php

use Mockery\MockInterface;
use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Listeners\DispatchGetConstellationById;
use Seatplus\Eveapi\Models\Universe\System;

beforeEach(function () {
    Queue::fake();
});

it('dispatches job when constellation is null', function () {

    $system = System::factory()->noConstellation()->make();

    $event = mock(UniverseSystemCreated::class, fn ($mock) => $mock->system = $system);

    $listener = new DispatchGetConstellationById;
    $listener->handle($event);

    Queue::assertPushedOn('default',
        ResolveUniverseConstellationByConstellationIdJob::class,
        fn ($job) => $job->constellation_id === $system->constellation_id
    );
});

it('does not dispatch job when constellation is not null', function () {
    $event = mock(UniverseSystemCreated::class, function (MockInterface $mock) {

        $mock->system = System::factory()->make();
    })->makePartial();

    $listener = new DispatchGetConstellationById;
    $listener->handle($event);

    Queue::assertNotPushed(ResolveUniverseConstellationByConstellationIdJob::class);
});
