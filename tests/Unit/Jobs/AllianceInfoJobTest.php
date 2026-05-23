<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

it('returns early if batch is cancelled', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = mock(AllianceInfoJob::class, function ($mock) {
        $mock->shouldReceive('batching')->andReturn(true);
        $mock->shouldReceive('batch->cancelled')->once()->andReturn(true);
    })->shouldAllowMockingProtectedMethods()->makePartial();

    $job->executeJob($esi);
});

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new AllianceInfoJob(12345);
    $job->executeJob($esi);

    expect(AllianceInfo::where('alliance_id', 12345)->count())->toBe(0);
});
