<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Models\Universe\Group;

it('creates group', function () {
    $mock_data = Group::factory()->make();

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('universe->getUniverseGroupsGroupId', $dto);

    Event::fakeFor(fn () => runJob(new ResolveUniverseGroupByIdJob($mock_data->group_id)));

    expect(Group::first())
        ->group_id->toBe($mock_data->group_id);
});

it('skips db write when response is a cached load', function () {
    $mock_data = Group::factory()->make();

    $dto = (object) array_merge(['isCachedLoad' => true], $mock_data->toArray());
    mockEsiClient('universe->getUniverseGroupsGroupId', $dto);

    Event::fakeFor(fn () => runJob(new ResolveUniverseGroupByIdJob($mock_data->group_id)));

    expect(Group::count())->toBe(0);
});
