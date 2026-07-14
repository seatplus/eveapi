<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\Assets;

use Illuminate\Support\Collection;

/**
 * Resolves, for each asset, the top-level location and top-level item it ultimately sits in, by
 * walking up the self-referential container chain (a child's location_id is its parent's item_id)
 * until a location_id that isn't a sibling item_id (i.e. a real Location) is reached:
 *
 *  - root_location_id — the top-level Location (station/structure) the asset is in, at any depth.
 *  - root_item_id     — the top-level item (direct child of that Location) the asset lives in; for a
 *                       top-level asset this is its own item_id. Lets the assets view resolve
 *                       "which top-level items match/contain a match" as one flat indexed query.
 *
 * Kept in PHP (no raw SQL) for the per-character ingest path: the asset set is already in memory
 * there, so this is an O(N × depth) walk; the caller writes the results in its existing upsert.
 */
class ResolveAssetRootLocations
{
    /**
     * @param  Collection<int, array{item_id: int, location_id: int}>  $assets
     * @return Collection<int, array{root_location_id: int, root_item_id: int}> keyed by item_id
     */
    public function resolve(Collection $assets): Collection
    {
        // item_id => location_id lookup, built typed so the walk stays int-typed end to end.
        $locationByItemId = [];
        foreach ($assets as $asset) {
            $locationByItemId[$asset['item_id']] = $asset['location_id'];
        }

        return $assets->mapWithKeys(function ($asset) use ($locationByItemId): array {
            $locationId = $asset['location_id'];
            $rootItemId = $asset['item_id']; // a top-level asset is its own root item
            $seen = [];

            // Walk up while the current location_id points at another of these assets (its
            // container). $rootItemId trails one step behind $locationId, so when the loop exits it
            // holds the item whose location_id is the real (root) Location — the top-level ancestor.
            // The $seen guard makes malformed cyclic data terminate instead of looping forever.
            while (isset($locationByItemId[$locationId]) && ! isset($seen[$locationId])) {
                $seen[$locationId] = true;
                $rootItemId = $locationId;
                $locationId = $locationByItemId[$locationId];
            }

            return [$asset['item_id'] => [
                'root_location_id' => $locationId,
                'root_item_id' => $rootItemId,
            ]];
        });
    }
}
