<?php

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\HydrateMaintenanceBase;
use Seatplus\Eveapi\Models\Assets\Asset;

class UpdateAssetSystemRegionJob extends HydrateMaintenanceBase
{
    public function __construct(private ?int $assetable_id = null)
    {
    }

    public function handle(): void
    {
        // check if batch is running
        if ($this->batch()?->cancelled()) {
            return;
        }

        // get all assets with missing system_id and region_id
        $assets = $this->getAssetsWithMissingSystemAndRegionInfo();

        $assets->each(fn ($asset) => $asset->update([
            'solar_system_id' => $asset->location->locatable->system->system_id,
            'region_id' => $asset->location->locatable->system->region->region_id,
        ]));
    }

    private function getAssetsWithMissingSystemAndRegionInfo(): Collection
    {
        return Asset::query()
            ->when($this->assetable_id, fn ($query) => $query->where('assetable_id', $this->assetable_id))
            ->where('location_type', '<>', 'item')
            ->has('location.locatable.system.region')
            ->with('location.locatable.system.region')
            ->get();
    }
}
