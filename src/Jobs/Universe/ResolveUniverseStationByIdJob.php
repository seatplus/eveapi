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

use Seatplus\Eveapi\DataTransferObjects\Responses\Universe\StationResponse;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Traits\HasPathValues;

class ResolveUniverseStationByIdJob extends EsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    const array STATION_IDS_RANGE = [60000000, 64000000];

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

    #[\Override]
    public function tags(): array
    {
        return [
            'resolve',
            'universe',
            'station',
            'location_id:'.$this->location_id,
        ];
    }

    #[\Override]
    public function middleware(): array
    {
        return [
            ...parent::middleware(),
        ];
    }

    #[\Override]
    public function executeJob(): void
    {
        // If rate limited or not within ids range skip execution
        if (($this->location_id < head(self::STATION_IDS_RANGE) || $this->location_id > last(self::STATION_IDS_RANGE))) {
            return;
        }

        $result = $this->retrieve();

        $data = StationResponse::from($result->data);

        Station::updateOrCreate([
            'station_id' => $this->location_id,
        ], [
            'type_id' => $data->type_id,
            'name' => $data->name,
            'owner_id' => $data->owner,
            'race_id' => $data->race_id,
            'system_id' => $data->system_id,
            'reprocessing_efficiency' => $data->reprocessing_efficiency,
            'reprocessing_stations_take' => $data->reprocessing_stations_take,
            'max_dockable_ship_volume' => $data->max_dockable_ship_volume,
            'office_rental_cost' => $data->office_rental_cost,
        ])->touch();

        Location::updateOrCreate([
            'location_id' => $this->location_id,
        ], [
            'locatable_id' => $this->location_id,
            'locatable_type' => Station::class,
        ]);
    }
}
