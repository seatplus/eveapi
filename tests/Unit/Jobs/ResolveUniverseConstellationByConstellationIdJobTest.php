<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Models\Universe\Constellation;

it('creates constellation', function () {
    $data = (object) [
        'constellation_id' => 20000001,
        'region_id' => 10000001,
        'name' => 'Test Constellation',
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    new ResolveUniverseConstellationByConstellationIdJob(20000001)->executeJob($esi);

    expect(Constellation::count())->toBe(1)
        ->and(Constellation::first()->constellation_id)->toBe(20000001);
});

it('skips db write when response is a cached load', function () {
    $data = (object) [
        'isCachedLoad' => true,
        'constellation_id' => 20000001,
        'region_id' => 10000001,
        'name' => 'Test Constellation',
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    new ResolveUniverseConstellationByConstellationIdJob(20000001)->executeJob($esi);

    expect(Constellation::count())->toBe(0);
});
