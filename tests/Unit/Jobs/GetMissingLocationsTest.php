<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\GetMissingLocations;
use Seatplus\Eveapi\Models\Universe\Location;

it('adds resolve location job to batch', function () {

    Event::fakeFor(fn () => Location::factory()->create());

    $job = mock(GetMissingLocations::class)->makePartial();
    $job->shouldReceive('batch->cancelled')->andReturn(false);
    $job->shouldReceive('batch->add')->once();

    $job->handle();

    expect(true)->toBeTrue();
});
