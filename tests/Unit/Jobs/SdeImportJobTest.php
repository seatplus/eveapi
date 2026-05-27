<?php

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Artisan;
use Seatplus\Eveapi\Jobs\Seatplus\SdeImportJob;

it('calls seatplus:sde-import artisan command', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('seatplus:sde-import');

    (new SdeImportJob)->handle();
});

it('is unique', function () {
    expect(new SdeImportJob)->toBeInstanceOf(ShouldBeUnique::class);
});

it('has extended timeout', function () {
    $job = new SdeImportJob;

    expect($job->timeout)->toBe(600);
});
