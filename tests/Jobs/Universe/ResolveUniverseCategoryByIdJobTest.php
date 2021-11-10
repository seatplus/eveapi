<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

it('creates category', function () {
    $mock_data = buildCategoryMockData();

    expect(Category::first())->toBeNull();

    Event::fakeFor(fn () => ResolveUniverseCategoryByIdJob::dispatchSync($mock_data->category_id));

    expect(Category::first())
        ->first()->category_id->toBe($mock_data->category_id);
});


// Helpers
function buildCategoryMockData()
{
    $mock_data = Category::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
