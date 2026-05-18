<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Region;

class ResolveUniverseRegionByRegionIdJob extends EsiJob
{
    public function __construct(private int $region_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'region', "region_id:{$this->region_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->universe()->getUniverseRegionsRegionId($this->region_id);

        Region::firstOrCreate(
            ['region_id' => $response->region_id],
            ['name' => $response->name, 'description' => $response->description]
        );
    }
}
