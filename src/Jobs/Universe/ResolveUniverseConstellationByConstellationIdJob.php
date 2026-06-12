<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseConstellationsConstellationId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Constellation;

final class ResolveUniverseConstellationByConstellationIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseConstellationsConstellationId::class;

    public function __construct(public int $constellationId) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'constellation', "constellation_id:{$this->constellationId}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->constellationId);

        if ($response->isCachedLoad) {
            return;
        }

        Constellation::firstOrCreate(
            ['constellation_id' => $response->constellation_id],
            ['region_id' => $response->region_id, 'name' => $response->name]
        );
    }
}
