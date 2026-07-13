<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Assets\Asset;

class EnrichAssetTypeGroupCategoryJob extends HydrateMaintenanceBase
{
    /**
     * Re-trigger enrichment when SDE data lands late, but only if at least one
     * asset is actually waiting for a now-complete type->group->category chain.
     *
     * The Type/Group/Category observers all call this the moment a link of that
     * chain resolves, so the denormalized columns self-heal without waiting for
     * the next full character batch. The guard keeps a burst of SDE inserts from
     * dispatching redundant no-op jobs.
     */
    public static function dispatchForWaitingAssets(): void
    {
        if (! Asset::query()->needsUniverseEnrichment()->exists()) {
            return;
        }

        self::dispatch()->onQueue('high');
    }

    #[\Override]
    public function handle(): void
    {

        // check if batch is running
        if ($this->batch()?->cancelled()) {
            return;
        }

        // get all assets with missing group_id and category_id
        $assets = $this->getAssetsWithMissingGroupAndCategoryInfo();

        $assets->each(fn (Asset $asset) => $asset->update([
            'type_name_normalized' => $asset->type->name_normalized,
            'group_id' => $asset->type->group->group_id,
            'group_name_normalized' => $asset->type->group->name_normalized,
            'category_id' => $asset->type->group->category->category_id,
            'category_name_normalized' => $asset->type->group->category->name_normalized,
        ]));
    }

    private function getAssetsWithMissingGroupAndCategoryInfo(): Collection
    {
        return Asset::query()
            ->needsUniverseEnrichment()
            ->with('type.group.category')
            ->get();
    }
}
