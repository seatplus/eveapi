<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Universe\GetUniverseSystemsSystemId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Universe\System;

final class ResolveUniverseSystemBySystemIdJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetUniverseSystemsSystemId::class;

    public function __construct(private readonly int $system_id) {}

    #[\Override]
    public function tags(): array
    {
        return ['resolve', 'universe', 'system', "system_id:{$this->system_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->system_id);

        System::firstOrCreate(
            ['system_id' => $response->system_id],
            [
                'constellation_id' => $response->constellation_id,
                'name' => $response->name,
                'security_status' => $response->security_status,
                'security_class' => $response->security_class,
            ]
        );
    }
}
