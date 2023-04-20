<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

test('new type id dispatches group job if group is not present', function () {
    Queue::fake();

    $type = Type::factory()->create();

    Queue::assertPushedOn('high', ResolveUniverseGroupByIdJob::class);
});

test('new type does not dispatches group job if group is present', function () {
    Queue::fake();

    $group = Group::factory()->create();

    $type = Type::factory()->create([
        'group_id' => $group->group_id,
    ]);

    Queue::assertNotPushed(ResolveUniverseGroupByIdJob::class);
});
