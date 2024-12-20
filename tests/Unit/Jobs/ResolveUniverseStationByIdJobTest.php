<?php

use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;

it('returns correct tags array for universe station job', function () {
    $job = new ResolveUniverseStationByIdJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'resolve',
        'universe',
        'station',
        'location_id:12345',
    ]);
});
