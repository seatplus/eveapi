<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseGroupsGroupId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Group;

class ResolveUniverseGroupByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseGroupsGroupId::class;

    public function __construct(private int $group_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'group', "group_id:{$this->group_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = static::OPERATION_CLASS::execute($esi, $this->group_id);

        Group::firstOrCreate(
            ['group_id' => $response->group_id],
            ['category_id' => $response->category_id, 'name' => $response->name, 'published' => $response->published]
        );
    }
}
