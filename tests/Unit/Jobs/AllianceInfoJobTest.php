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
    $esi = mockEsiClient('alliance->getAlliancesAllianceId', (object) ['isCachedLoad' => true]);

    $job = mock(AllianceInfoJob::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->alliance_id = 12345;
    $job->executeJob($esi);

    expect(true)->toBeTrue();
});
