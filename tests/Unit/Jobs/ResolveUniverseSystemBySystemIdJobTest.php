<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Models\Universe\System;

it('creates system', function () {
    $data = (object) [
        'system_id' => 30000001,
        'constellation_id' => 20000001,
        'name' => 'Test System',
        'security_status' => 0.9,
        'security_class' => 'A',
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    new ResolveUniverseSystemBySystemIdJob(30000001)->executeJob($esi);

    expect(System::count())->toBe(1)
        ->and(System::first()->system_id)->toBe(30000001);
});

it('skips db write when response is a cached load', function () {
    $data = (object) [
        'isCachedLoad' => true,
        'system_id' => 30000001,
        'constellation_id' => 20000001,
        'name' => 'Test System',
        'security_status' => 0.9,
        'security_class' => 'A',
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    new ResolveUniverseSystemBySystemIdJob(30000001)->executeJob($esi);

    expect(System::count())->toBe(0);
});
