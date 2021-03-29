<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;

class GetMissingTypesFromCorporationMemberTracking extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $type_ids = CorporationMemberTracking::doesntHave('ship')->pluck('ship_type_id')->unique()->values();

        $type_ids->each(fn($id) => ResolveUniverseTypeByIdJob::dispatch($id)->onQueue('high'));
    }
}