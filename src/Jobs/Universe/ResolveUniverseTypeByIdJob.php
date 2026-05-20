<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseTypesTypeId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Type;

final class ResolveUniverseTypeByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseTypesTypeId::class;

    public function __construct(private readonly int $type_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'type', "type_id:{$this->type_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->type_id);

        Type::firstOrCreate(
            ['type_id' => $response->type_id],
            [
                'group_id' => $response->group_id,
                'name' => $response->name,
                'description' => $response->description,
                'published' => $response->published,
                'capacity' => $response->capacity,
                'graphic_id' => $response->graphic_id,
                'icon_id' => $response->icon_id,
                'market_group_id' => $response->market_group_id,
                'mass' => $response->mass,
                'packaged_volume' => $response->packaged_volume,
                'portion_size' => $response->portion_size,
                'radius' => $response->radius,
                'volume' => $response->volume,
            ]
        );
    }
}
