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

namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;

use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class GetMissingLocationFromAssets extends HydrateMaintenanceBase
{
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        // First do the character assets

        Asset::whereDoesntHave('location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]))
            // limit result to assetable_type CharacterInfo
            ->where('assetable_type', CharacterInfo::class)
            ->AssetsLocationIds()
            ->inRandomOrder()
            ->addSelect('assetable_id')
            ->get()
            ->unique('location_id')
            ->filter()
            ->each(function ($asset) {
                $refresh_token = RefreshToken::find($asset->assetable_id);

                if (is_null($refresh_token)) {
                    return;
                }

                $this->batch()->add([
                    new ResolveLocationJob($asset->location_id, $refresh_token),
                ]);
            });

        // Then do the corporation assets
        // TODO: Implement GetMissingLocationFromAssets for corporation assets
    }
}
