<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseGroupsGroupId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\Group;

final class ResolveUniverseGroupByIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseGroupsGroupId::class;

    public function __construct(private readonly int $groupId) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'group', "group_id:{$this->groupId}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->groupId);

        if ($response->isCachedLoad) {
            return;
        }

        Group::firstOrCreate(
            ['group_id' => $response->group_id],
            ['category_id' => $response->category_id, 'name' => $response->name, 'published' => $response->published]
        );
    }
}
