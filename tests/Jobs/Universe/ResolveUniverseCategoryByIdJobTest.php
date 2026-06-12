<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Universe\Category;

it('creates category', function () {
    $mockData = Category::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->toArray()));

    $job = new ResolveUniverseCategoryByIdJob($mockData->category_id);
    $job->executeJob($esi);

    expect(Category::where('category_id', $mockData->category_id)->exists())->toBeTrue();
});

it('skips db write when response is a cached load', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new ResolveUniverseCategoryByIdJob(12345);
    $job->executeJob($esi);

    expect(Category::count())->toBe(0);
});
