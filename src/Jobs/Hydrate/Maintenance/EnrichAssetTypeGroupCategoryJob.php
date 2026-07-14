<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Seatplus\Eveapi\Models\Assets\Asset;

class EnrichAssetTypeGroupCategoryJob extends HydrateMaintenanceBase
{
    // $characterId scopes the enrichment to a single character's assets — the per-character
    // update chain only ever adds that character's rows. Left null (MaintenanceJob / observers)
    // it enriches every unenriched asset globally. Scoping avoids re-scanning the whole assets
    // table (all characters) on every character batch. Declared (not promoted) with a default so
    // it stays initialised even when a test builds the job via a partial mock (no constructor call).
    public ?int $characterId = null;

    public function __construct(?int $characterId = null)
    {
        $this->characterId = $characterId;
    }

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

        // Bulk enrich: one UPDATE per distinct type (assets that share a type share their
        // group/category), instead of one UPDATE per asset row.
        DB::transaction(fn () => $this->getAssetsToEnrich()
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

    private function getAssetsToEnrich(): Collection
    {
        return Asset::query()
            ->needsUniverseEnrichment()
            ->when($this->characterId, fn (Builder $query) => $query->where('assetable_id', $this->characterId))
            ->with('type.group.category')
            ->get();
    }
}
