<?php

it('returns early if resonse is cached', function () {

    $response = \Mockery::mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(\Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(\Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking::query()->count())->toEqual(0);
});
