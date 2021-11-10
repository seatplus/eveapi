<?php


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

it('creates group', function () {
    $mock_data = buildGroupMockData();

    expect($mock_data)
        ->group_id->toBeInt();

    expect(Group::first())->toBeNull();

    Event::fakeFor(fn () => \Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob::dispatchSync($mock_data->group_id));

    expect(Group::first())
        ->first()->category_id->toBe($mock_data->category_id);
});


// Helpers
function buildGroupMockData()
{
    $mock_data = Group::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
