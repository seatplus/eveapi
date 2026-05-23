<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;

it('returns early if resonse is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationMemberTrackingJob(12345);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(CorporationMemberTracking::query()->count())->toEqual(0);
});
