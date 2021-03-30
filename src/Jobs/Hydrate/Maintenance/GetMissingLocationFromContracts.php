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

use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class GetMissingLocationFromContracts extends HydrateMaintenanceBase
{
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        Contract::query()
            ->where(function (Builder $query) {
                $query->whereNotNull('start_location_id')
                    ->whereDoesntHave('start_location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]));
            })
            ->orWhere(function (Builder $query) {
                $query->whereNotNull('end_location_id')
                    ->whereDoesntHave('end_location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]));
            })
            //->whereDoesntHave('start_location', fn ($query) => $query->whereNotNull('start_location_id'))
            //->orWhereDoesntHave('end_location', fn ($query) => $query->whereNotNull('end_location_id'))
            ->inRandomOrder()
            ->get()
            ->each(function ($contract) {
                $unknown_location_ids = collect();

                if (is_null($contract->start_location) || $this->isNotStationOrStructure($contract->start_location)) {
                    $unknown_location_ids->push($contract->start_location_id);
                }

                if (is_null($contract->end_location) || $this->isNotStationOrStructure($contract->end_location)) {
                    $unknown_location_ids->push($contract->end_location_id);
                }

                $refresh_token = RefreshToken::find($contract->issuer_id) ?? RefreshToken::find($contract->assignee_id);

                if (is_null($refresh_token)) {
                    return;
                }

                $unknown_location_ids
                    ->filter()
                    ->unique()
                    ->each(fn ($location_id) => $this
                        ->batch()
                        ->add([
                            new ResolveLocationJob($location_id, $refresh_token),
                        ])
                    );
            });
    }

    private function isNotStationOrStructure($location): bool
    {
        return ! (is_a($location, Structure::class) || is_a($location, Structure::class));
    }
}
