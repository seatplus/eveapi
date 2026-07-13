<?php

declare(strict_types=1);

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Observers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Group;

class GroupObserver
{
    private Group $group;

    public function created(Group $group): void
    {
        $this->group = $group;

        $this->handleCategory();
        $this->handleAssetsName();
        $this->handleEnrichment();
    }

    private function handleCategory(): void
    {
        if ($this->group->category) {
            return;
        }

        ResolveUniverseCategoryByIdJob::dispatch($this->group->category_id)->onQueue('high');
    }

    private function handleAssetsName(): void
    {
        if (! in_array($this->group->category_id, [2, 6, 22, 23, 46, 65])) {
            return;
        }

        Asset::whereHas('type.group', function (Builder $query) {
            // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
            $query->where('group_id', $this->group->group_id)
                ->whereIn('category_id', [2, 6, 22, 23, 46, 65]);
        })
            ->get()
            ->unique('assetable_id')
            ->whenNotEmpty(function (Collection $assets) {
                $assets->each(function (Asset $asset) {
                    CharacterAssetsNameJob::dispatch($asset->assetable_id)->onQueue('high');
                });
            });
    }

    /**
     * Self-heal denormalized asset columns when SDE data lands late.
     *
     * CharacterAssetJob resolves unknown types asynchronously, so the enrich
     * job chained after it usually finds an unresolved type->group->category
     * chain and skips those assets. Once the group (with a resolvable category)
     * arrives we re-trigger the enrichment so the backfill happens without
     * waiting for the next full batch.
     */
    private function handleEnrichment(): void
    {
        if (! $this->group->category) {
            return;
        }

        $enrichableAssetsExist = Asset::query()
            ->whereNull('group_id')
            ->has('type.group.category')
            ->exists();

        if (! $enrichableAssetsExist) {
            return;
        }

        EnrichAssetTypeGroupCategoryJob::dispatch()->onQueue('high');
    }
}
