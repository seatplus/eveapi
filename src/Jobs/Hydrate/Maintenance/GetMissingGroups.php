<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseGroupByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Type;

class GetMissingGroups extends HydrateMaintenanceBase
{

    public function handle()
    {

        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $unknown_type_ids = Type::whereDoesntHave('group')->pluck('group_id')->unique()->values();

        $jobs = $unknown_type_ids->map(fn($id) => new ResolveUniverseGroupByIdJob($id));

        $this->batch()->add(
            $jobs->toArray()
        );

    }
}