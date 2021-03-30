<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;

class GetMissingAssetsNames extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        Asset::whereHas('type.group', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->whereIn('category_id', [2, 6, 22, 23, 46, 65]);
        })
            ->pluck('assetable_id')
            ->unique()
            ->whenNotEmpty(function ($collection)  {
                return $collection->each(function ($assetable_id) {
                    $job_container = new JobContainer([
                        'refresh_token' => RefreshToken::find($assetable_id),
                    ]);

                    $this->batch()->add([
                        new CharacterAssetsNameJob($job_container)
                    ]);
                });
            });
    }
}