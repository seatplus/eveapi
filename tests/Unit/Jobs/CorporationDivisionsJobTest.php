<?php

use Seatplus\Eveapi\Models\Corporation\CorporationDivision;

it('has tags', function () {
    $job = new \Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob(1);

    expect($job->tags())->toEqual([
        'corporation',
        'corporation_id: 1',
        'divisions',
    ]);
});

it('has middleware', function () {
    $job = new \Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob(1);

    expect($job->middleware())->toBeArray()
        ->and($job->middleware())->toHaveCount(2)
        ->and($job->middleware()[0])->toBeInstanceOf(\Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware::class);
});

it('returns early if response is cached', function () {

    $response = mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(\Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(CorporationDivision::all())->toHaveCount(0);
});
