<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Models\Corporation\CorporationDivision;

it('has tags', function () {
    $job = new CorporationDivisionsJob(1);

    expect($job->tags())->toEqual([
        'corporation',
        'corporation_id:1',
        'divisions',
    ]);
});

it('has middleware', function () {
    $job = new CorporationDivisionsJob(1);

    expect($job->middleware())->toBeArray()
        ->and($job->middleware())->toHaveCount(2)
        ->and($job->middleware()[0])->toBeInstanceOf(EsiProactiveRateLimitMiddleware::class);
});

it('returns early if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationDivisionsJob(1);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(CorporationDivision::all())->toHaveCount(0);
});
