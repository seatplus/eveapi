<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseRegionsRegionId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Region;

final class ResolveUniverseRegionByRegionIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseRegionsRegionId::class;

    public function __construct(private readonly int $region_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'region', "region_id:{$this->region_id}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->region_id);

        if ($response->isCachedLoad) {
            return;
        }

        Region::firstOrCreate(
            ['region_id' => $response->region_id],
            ['name' => $response->name, 'description' => $response->description]
        );
    }
}
