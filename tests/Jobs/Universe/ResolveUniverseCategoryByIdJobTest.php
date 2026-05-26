<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Universe\Category;

it('creates category', function () {
    $mock_data = Category::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new ResolveUniverseCategoryByIdJob($mock_data->category_id);
    $job->executeJob($esi);

    expect(Category::where('category_id', $mock_data->category_id)->exists())->toBeTrue();
});

it('skips db write when response is a cached load', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new ResolveUniverseCategoryByIdJob(12345);
    $job->executeJob($esi);

    expect(Category::count())->toBe(0);
});
