<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

it('returns early if batch is cancelled', function () {
    // No expectations on the client: any ESI call means the guard did not return early.
    $esi = Mockery::mock(EsiClient::class);

    [$job, $batch] = new AllianceInfoJob(12345)->withFakeBatch();
    $batch->cancel();

    $job->executeJob($esi);

    expect(AllianceInfo::where('alliance_id', 12345)->count())->toBe(0);
});

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new AllianceInfoJob(12345);
    $job->executeJob($esi);

    expect(AllianceInfo::where('alliance_id', 12345)->count())->toBe(0);
});
