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
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class GetMissingLocationFromCorporationMemberTracking extends HydrateMaintenanceBase
{
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $find_corporation_refresh_token = new FindCorporationRefreshToken;

        CorporationMemberTracking::whereDoesntHave('location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]))
            ->inRandomOrder()
            ->get()
            ->each(function (CorporationMemberTracking $corporation_member_tracking) use ($find_corporation_refresh_token) {
                $refresh_token = $find_corporation_refresh_token($corporation_member_tracking->corporation_id, 'esi-corporations.track_members.v1', 'Director') ?? RefreshToken::find($corporation_member_tracking->character_id);

                if ($refresh_token) {
                    $this->batch()->add([
                        new ResolveLocationJob($corporation_member_tracking->location_id, $refresh_token),
                    ]);
                }

                return false;
            });
    }
}
