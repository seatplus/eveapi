<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Group;

class ResolveUniverseGroupByIdJob extends EsiJob
{
    public function __construct(private int $group_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'group', "group_id:{$this->group_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->universe()->getUniverseGroupsGroupId($this->group_id);

        Group::firstOrCreate(
            ['group_id' => $response->group_id],
            ['category_id' => $response->category_id, 'name' => $response->name, 'published' => $response->published]
        );
    }
}
