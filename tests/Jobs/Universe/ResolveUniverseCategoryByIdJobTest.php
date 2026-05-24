<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Universe\Category;

it('creates category', function () {
    $mock_data = Category::factory()->make();

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('universe->getUniverseCategoriesCategoryId', $dto);

    Event::fakeFor(fn () => runJob(new ResolveUniverseCategoryByIdJob($mock_data->category_id)));

    expect(Category::first())
        ->category_id->toBe($mock_data->category_id);
});

it('skips db write when response is a cached load', function () {
    $mock_data = Category::factory()->make();

    $dto = (object) array_merge(['isCachedLoad' => true], $mock_data->toArray());
    mockEsiClient('universe->getUniverseCategoriesCategoryId', $dto);

    Event::fakeFor(fn () => runJob(new ResolveUniverseCategoryByIdJob($mock_data->category_id)));

    expect(Category::count())->toBe(0);
});
