<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Corporation\CorporationDivision;

it('has tags', function () {
    $job = new CorporationDivisionsJob(1);

    expect($job->tags())->toEqual([
        'corporation',
        'corporation_id: 1',
        'divisions',
    ]);
});

it('has middleware', function () {
    $job = new CorporationDivisionsJob(1);

    expect($job->middleware())->toBeArray()
        ->and($job->middleware())->toHaveCount(3)
        ->and($job->middleware()[0])->toBeInstanceOf(HasRequiredScopeMiddleware::class)
        ->and($job->middleware()[1])->toBeInstanceOf(EsiProactiveRateLimitMiddleware::class);
});

it('returns early if response is cached', function () {

    $response = mock(EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(CorporationDivisionsJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(CorporationDivision::all())->toHaveCount(0);
});
