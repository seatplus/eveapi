<?php

it('has correct tags', function () {
    $job = new \Seatplus\Eveapi\Jobs\Seatplus\MaintenanceJob;

    expect($job->tags())->toBeArray()
        ->and($job->tags())->toBe(['Maintenance']);
});
