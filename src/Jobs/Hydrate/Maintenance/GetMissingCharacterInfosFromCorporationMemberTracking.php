<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;

class GetMissingCharacterInfosFromCorporationMemberTracking extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $character_ids = CorporationMemberTracking::doesntHave('character')->pluck('character_id')->unique()->values();

        $character_ids->each(function ($character_id) {
            $job = new JobContainer([
                'character_id' => $character_id,
            ]);

            CharacterInfoJob::dispatch($job)->onQueue('high');
        });
    }
}