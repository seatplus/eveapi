<?php

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\Assets\Asset;

class EnrichAssetTypeGroupCategoryJob extends HydrateMaintenanceBase
{
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
            ->whereNull('group_id')
            ->has('type.group.category')
            ->with('type.group.category')
            ->get();
    }
}
