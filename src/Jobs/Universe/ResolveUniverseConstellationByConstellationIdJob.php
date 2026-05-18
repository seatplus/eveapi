<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Constellation;

class ResolveUniverseConstellationByConstellationIdJob extends EsiJob
{
    public function __construct(public int $constellation_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'constellation', "constellation_id:{$this->constellation_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->universe()->getUniverseConstellationsConstellationId($this->constellation_id);

        Constellation::firstOrCreate(
            ['constellation_id' => $response->constellation_id],
            ['region_id' => $response->region_id, 'name' => $response->name]
        );
    }
}
