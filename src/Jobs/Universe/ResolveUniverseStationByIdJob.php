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

namespace Seatplus\Eveapi\Jobs\Universe;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Traits\HasPathValues;

class ResolveUniverseStationByIdJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    const STATION_IDS_RANGE = [60000000, 64000000];

    public function __construct(
        public int $location_id
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/universe/stations/{station_id}/',
            version: 'v2',
        );

        $this->setPathValues([
            'station_id' => $this->location_id,
        ]);
    }

    public function tags(): array
    {
        return [
            'resolve',
            'universe',
            'station',
            'location_id:' . $this->location_id,
        ];
    }

    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    public function executeJob(): void
    {
        // If rate limited or not within ids range skip execution
        if (($this->location_id < head(self::STATION_IDS_RANGE) || $this->location_id > last(self::STATION_IDS_RANGE))) {
            return;
        }

        $result = $this->retrieve();

        if ($result->isCachedLoad()) {
            return;
        }

        Station::updateOrCreate([
            'station_id' => $this->location_id,
        ], [
            'type_id' => $result->type_id,
            'name' => $result->name,
            'owner_id' => $result->owner ?? null,
            'race_id' => $result->race_id ?? null,
            'system_id' => $result->system_id,
            'reprocessing_efficiency' => $result->reprocessing_efficiency,
            'reprocessing_stations_take' => $result->reprocessing_stations_take,
            'max_dockable_ship_volume' => $result->max_dockable_ship_volume,
            'office_rental_cost' => $result->office_rental_cost,
        ])->touch();

        Location::updateOrCreate([
            'location_id' => $this->location_id,
        ], [
            'locatable_id' => $this->location_id,
            'locatable_type' => Station::class,
        ]);
    }
}
