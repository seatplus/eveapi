<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\System;

beforeEach(function () {
    Queue::fake();
});

it('has tags', function () {
    $job = new ResolveUniverseRegionByRegionIdJob(10000002);

    expect($job->tags())->toContain('region', 'resolve', 'universe', 'region_id:10000002');
});

it('resolves system', function () {
    $mock_data = System::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    expect(System::all())->toHaveCount(0);

    $job = new ResolveUniverseSystemBySystemIdJob($mock_data->system_id);
    $job->executeJob($esi);

    expect(System::all())->toHaveCount(1);
});

it('resolves constellation', function () {
    $mock_data = Constellation::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    expect(Constellation::all())->toHaveCount(0);

    $job = new ResolveUniverseConstellationByConstellationIdJob($mock_data->constellation_id);
    $job->executeJob($esi);

    expect(Constellation::all())->toHaveCount(1);
});

it('resolves region', function () {
    $mock_data = Region::factory()->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    expect(Region::all())->toHaveCount(0);

    $job = new ResolveUniverseRegionByRegionIdJob($mock_data->region_id);
    $job->executeJob($esi);

    expect(Region::all())->toHaveCount(1);
});
