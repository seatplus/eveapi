<?php

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\RefreshToken;

class GetMissingCharacterInfos extends HydrateMaintenanceBase
{
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $jobs = RefreshToken::query()
            ->whereDoesntHave('character')
            ->pluck('character_id')
            ->unique()
            ->values()
            ->map(fn (int $character_id) => new CharacterInfoJob($character_id));

        $this->batch()->add(
            $jobs->toArray()
        );
    }
}
