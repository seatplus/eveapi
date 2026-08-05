<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Artisan;
use Seatplus\Eveapi\Jobs\Seatplus\SdeImportJob;

it('calls seatplus:sde-import artisan command', function () {
    // Swap in a double of the Kernel *contract*: Artisan::shouldReceive() would build it
    // from the resolved concrete kernel, which Testbench declares final.
    $kernel = Mockery::mock(ConsoleKernel::class);
    $kernel->shouldReceive('call')
        ->once()
        ->with('seatplus:sde-import');

    Artisan::swap($kernel);

    (new SdeImportJob)->handle();
});

it('is unique', function () {
    expect(new SdeImportJob)->toBeInstanceOf(ShouldBeUnique::class);
});

it('has extended timeout', function () {
    $job = new SdeImportJob;

    expect($job->timeout)->toBe(600);
});
