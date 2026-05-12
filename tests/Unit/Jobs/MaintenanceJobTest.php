<?php

use Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;

it('has correct tags', function () {
    $job = new MaintenanceJob;

    expect($job->tags())->toBeArray()
        ->and($job->tags())->toBe(['Maintenance']);
});
