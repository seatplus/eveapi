<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Seatplus\Eveapi\Models\Assets\Asset;

class EnrichAssetTypeGroupCategoryJob extends HydrateMaintenanceBase
{
    // $characterId scopes the enrichment to a single character's assets — the per-character
    // update chain only ever adds that character's rows. Left null (MaintenanceJob) it enriches
    // every unenriched asset globally. Scoping avoids re-scanning the whole assets table (all
    // characters) on every character batch. Declared (not promoted) with a default so it stays
    // initialised even when a test builds the job via a partial mock (no constructor call).
    public ?int $characterId = null;

    public function __construct(?int $characterId = null)
    {
        $this->characterId = $characterId;
    }

    #[\Override]
    public function handle(): void
    {
        // check if batch is running
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Bulk enrich: one UPDATE per distinct type (assets that share a type share their
        // group/category), instead of one UPDATE per asset row.
        DB::transaction(fn () => $this->getAssetsWithMissingGroupAndCategoryInfo()
            ->groupBy('type_id')
            ->each(function (Collection $assets): void {
                /** @var Asset $first */
                $first = $assets->first();
                $type = $first->type;

                Asset::query()
                    ->whereIn('item_id', $assets->pluck('item_id')->all())
                    ->update([
                        'type_name_normalized' => $type->name_normalized,
                        'group_id' => $type->group->group_id,
                        'group_name_normalized' => $type->group->name_normalized,
                        'category_id' => $type->group->category->category_id,
                        'category_name_normalized' => $type->group->category->name_normalized,
                    ]);
            }));
    }

    private function getAssetsWithMissingGroupAndCategoryInfo(): Collection
    {
        return Asset::query()
            ->whereNull('group_id')
            ->when($this->characterId, fn ($query) => $query->where('assetable_id', $this->characterId))
            ->has('type.group.category')
            ->with('type.group.category')
            ->get();
    }
}
