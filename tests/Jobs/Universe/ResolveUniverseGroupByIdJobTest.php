<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Models\Universe\Group;

it('creates group', function () {
    $mock_data = buildGroupMockData();

    expect($mock_data)
        ->group_id->toBeInt();

    expect(Group::first())->toBeNull();

    Event::fakeFor(fn () => (new ResolveUniverseGroupByIdJob($mock_data->group_id))->handle());

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
