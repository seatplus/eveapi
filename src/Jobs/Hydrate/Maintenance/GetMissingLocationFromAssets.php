<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class GetMissingLocationFromAssets extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $jobs = Asset::whereDoesntHave('location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]))
            ->AssetsLocationIds()
            ->inRandomOrder()
            ->addSelect('assetable_id', 'assetable_type')
            ->get()
            ->map(function ($asset) {
                if ($asset->assetable_type === CharacterInfo::class) {
                    $refresh_token = RefreshToken::find($asset->assetable_id);
                }

                return new ResolveLocationJob($asset->location_id, $refresh_token);
            });

        $this->batch()->add(
            $jobs->toArray()
        );
    }
}