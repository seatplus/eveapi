<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;

it('returns early if resonse is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('corporation->getCorporationsCorporationIdMembertracking')->andReturn(makeEsiResult([], isCachedLoad: true));

    $job = mock(CorporationMemberTrackingJob::class)->makePartial();
    $job->executeJob($esi);

    expect(CorporationMemberTracking::query()->count())->toEqual(0);
});
