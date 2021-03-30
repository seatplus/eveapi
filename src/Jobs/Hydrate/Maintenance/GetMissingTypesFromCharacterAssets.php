<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;

class GetMissingTypesFromCharacterAssets extends HydrateMaintenanceBase
{

    public function handle()
    {

        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $type_ids = Asset::doesntHave('type')->pluck('type_id')->unique()->values();

        $jobs = $type_ids->map(fn($id) => new ResolveUniverseTypeByIdJob($id));

        $this->batch()->add(
            $jobs->toArray()
        );
    }

}