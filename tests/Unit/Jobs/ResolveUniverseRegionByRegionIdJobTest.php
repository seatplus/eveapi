<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Models\Universe\Region;

it('creates region', function () {
    $data = (object) [
        'region_id' => 10000001,
        'name' => 'Test Region',
        'description' => 'A test region',
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    (new ResolveUniverseRegionByRegionIdJob(10000001))->executeJob($esi);

    expect(Region::count())->toBe(1)
        ->and(Region::first()->region_id)->toBe(10000001);
});

it('skips db write when response is a cached load', function () {
    $data = (object) [
        'isCachedLoad' => true,
        'region_id' => 10000001,
        'name' => 'Test Region',
        'description' => 'A test region',
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    (new ResolveUniverseRegionByRegionIdJob(10000001))->executeJob($esi);

    expect(Region::count())->toBe(0);
});
