<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;

it('returns early if batch is cancelled', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = mock(AllianceInfoJob::class, function ($mock) {
        $mock->shouldReceive('batching')->andReturn(true);
        $mock->shouldReceive('batch->cancelled')->once()->andReturn(true);
    })->shouldAllowMockingProtectedMethods()->makePartial();

    $job->executeJob($esi);

    expect(true)->toBeTrue();
});

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new AllianceInfoJob(12345);
    $method = new ReflectionMethod($job, 'executeJob');
    $method->invoke($job, $esi);

    expect(true)->toBeTrue();
});
