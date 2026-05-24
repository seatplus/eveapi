<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Universe\Type;

it('executes job and upserts type', function () {
    $data = (object) [
        'type_id' => 12345,
        'group_id' => 1,
        'name' => 'Test Type',
        'description' => 'Test Description',
        'published' => true,
        'capacity' => 100,
        'graphic_id' => 200,
        'icon_id' => 300,
        'market_group_id' => 400,
        'mass' => 500,
        'packaged_volume' => 600,
        'portion_size' => 700,
        'radius' => 800,
        'volume' => 900,
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    $job = new ResolveUniverseTypeByIdJob(12345);
    $job->executeJob($esi);

    expect(Type::count())->toEqual(1)
        ->and(Type::first()->type_id)->toEqual(12345);
});
