<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiSchema\Responses\UniverseConstellationsConstellationIdGet;
use Seatplus\EsiSchema\Responses\UniverseRegionsRegionIdGet;
use Seatplus\EsiSchema\Responses\UniverseSystemsSystemIdGet;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\System;

beforeEach(function () {
    Queue::fake();
});

it('resolves system', function () {
    $mock_data = System::factory()->make();

    $dto = UniverseSystemsSystemIdGet::from((object) $mock_data->toArray());

    mockEsiClient('universe->getUniverseSystemsSystemId', $dto);

    expect(System::all())->toHaveCount(0);

    runJob(new ResolveUniverseSystemBySystemIdJob($mock_data->system_id));

    expect(System::all())->toHaveCount(1);
});

it('resolves constellation', function () {
    $mock_data = Constellation::factory()->make();

    $dto = UniverseConstellationsConstellationIdGet::from((object) $mock_data->toArray());

    mockEsiClient('universe->getUniverseConstellationsConstellationId', $dto);

    expect(Constellation::all())->toHaveCount(0);

    runJob(new ResolveUniverseConstellationByConstellationIdJob($mock_data->constellation_id));

    expect(Constellation::all())->toHaveCount(1);
});

it('resolves region', function () {
    $mock_data = Region::factory()->make();

    $dto = UniverseRegionsRegionIdGet::from((object) $mock_data->toArray());

    mockEsiClient('universe->getUniverseRegionsRegionId', $dto);

    expect(Region::all())->toHaveCount(0);

    runJob(new ResolveUniverseRegionByRegionIdJob($mock_data->region_id));

    expect(Region::all())->toHaveCount(1);
});
