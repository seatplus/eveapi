<?php

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

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequestBody;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CharacterAssetsNameJob extends EsiBase implements HasPathValuesInterface, HasRequestBodyInterface, HasRequiredScopeInterface
{
    use HasRequiredScopes;
    use HasPathValues;
    use HasRequestBody;

    const CELESTIAL_CATEGORY = 2;
    const SHIP_CATEGORY = 6;
    const DEPLOYABLE_CATEGORY = 22;
    const STARBASE_CATEGORY = 23;
    const ORBITALS_CATEGORY = 46;
    const STRUCTURE_CATEGORY = 65;

    private Collection $asset_names;

    public function __construct(
        public int $character_id,
    ) {
        parent::__construct(
            method: 'post',
            endpoint: '/characters/{character_id}/assets/names/',
            version: 'v1',
        );

        $this->setRequiredScope('esi-assets.read_assets.v1');

        $this->setPathValues([
            'character_id' => $character_id,
        ]);

        $this->asset_names = collect();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'character',
            'character_id: ' . $this->character_id,
            'assets',
            'name',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        Asset::query()
            ->with('type.group')
            ->whereHas('type.group', function (Builder $query) {
                // Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
                $query->whereIn('category_id', [
                    self::CELESTIAL_CATEGORY, self::SHIP_CATEGORY, self::DEPLOYABLE_CATEGORY,
                    self::STARBASE_CATEGORY, self::ORBITALS_CATEGORY, self::STRUCTURE_CATEGORY,
                ]);
            })
            ->where('assetable_id', $this->character_id)
            ->select('item_id')
            ->where('is_singleton', true)
            ->pluck('item_id')
            ->chunk(1000)->each(function ($item_ids) {
                $clean_item_ids = $item_ids->flatten()->toArray();

                $this->setRequestBody($clean_item_ids);

                $response = $this->retrieve();

                // merge response into asset_names collection
                $this->asset_names = $this->asset_names->merge(collect($response));
            });

        // Update all assets in one go
        $this->asset_names
            // filter out "None" names
            ->filter(fn ($asset_name) => $asset_name->name !== 'None')
            // update asset names
            ->each(fn ($asset_name) => Asset::query()
                ->where('assetable_id', $this->character_id)
                ->where('item_id', $asset_name->item_id)
                ->update(['name' => $asset_name->name])
            );

    }
}
