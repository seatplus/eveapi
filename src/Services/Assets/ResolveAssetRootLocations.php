<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\Assets;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Models\Assets\Asset;

/**
 * Resolves each asset's root_location_id — the top-level location it ultimately sits in —
 * by walking up the self-referential container chain (a child's location_id is its parent's
 * item_id) until a location_id that isn't a sibling item_id (i.e. a real Location) is reached.
 *
 * Kept in PHP (no raw SQL) for the per-character ingest path: the asset set is already in
 * memory there, so this is an O(N × depth) walk plus a handful of grouped PK updates.
 */
class ResolveAssetRootLocations
{
    /**
     * @param  Collection<int, array{item_id: int, location_id: int}>  $assets
     * @return Collection<int, int> item_id => root_location_id
     */
    public function resolve(Collection $assets): Collection
    {
        $locationByItemId = $assets->pluck('location_id', 'item_id');

        return $assets->mapWithKeys(function (array $asset) use ($locationByItemId): array {
            $locationId = $asset['location_id'];
            $seen = [];

            // Walk up while the current location_id points at another of these assets; the
            // $seen guard makes malformed cyclic data terminate instead of looping forever.
            while ($locationByItemId->has($locationId) && ! isset($seen[$locationId])) {
                $seen[$locationId] = true;
                $locationId = $locationByItemId->get($locationId);
            }

            return [$asset['item_id'] => $locationId];
        });
    }

    /**
     * @param  Collection<int, int>  $rootByItemId  item_id => root_location_id
     */
    public function persist(Collection $rootByItemId): void
    {
        // One grouped update per distinct root location — a character's assets cluster in a
        // handful of stations/structures, so this is a few PK-indexed updates.
        $rootByItemId
            // preserveKeys so each group keeps its item_id keys (groupBy drops them by default).
            ->groupBy(fn (int $rootLocationId): int => $rootLocationId, preserveKeys: true)
            ->each(fn (Collection $group, int $rootLocationId) => Asset::query()
                ->whereIn('item_id', $group->keys()->all())
                ->update(['root_location_id' => $rootLocationId]));
    }
}
