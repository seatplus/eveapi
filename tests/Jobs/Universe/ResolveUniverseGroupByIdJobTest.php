<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Models\Universe\Group;

it('creates group', function () {
    $mock_data = Group::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new ResolveUniverseGroupByIdJob($mock_data->group_id);
    $job->executeJob($esi);

    expect(Group::where('group_id', $mock_data->group_id)->exists())->toBeTrue();
});

it('skips db write when response is a cached load', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new ResolveUniverseGroupByIdJob(12345);
    $job->executeJob($esi);

    expect(Group::count())->toBe(0);
});
