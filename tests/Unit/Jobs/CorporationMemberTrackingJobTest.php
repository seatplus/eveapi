<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;

it('returns early if resonse is cached', function () {

    $response = Mockery::mock(EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(CorporationMemberTrackingJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(CorporationMemberTracking::query()->count())->toEqual(0);
});
