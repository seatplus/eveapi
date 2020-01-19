<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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

namespace Seatplus\Eveapi\Actions\Location;

use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

class ResolveUniverseStationByIdAction extends RetrieveFromEsiBase implements HasPathValuesInterface
{
    const STATION_IDS_RANGE = [60000000, 64000000];

    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/universe/stations/{station_id}/';

    /**
     * @var string
     */
    protected $version = 'v2';

    /**
     * @var array
     */
    private $path_values = [];

    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function execute(int $location_id)
    {

        // If rate limited or not within ids range skip execution
        if($this->isEsiRateLimited() || ($location_id < head(self::STATION_IDS_RANGE) || $location_id > last(self::STATION_IDS_RANGE)))
            return;

        $this->setPathValues([
            'station_id' => $location_id,
        ]);

        $result = $this->retrieve();

        Station::updateOrCreate([
            'station_id' => $location_id,
        ], [
            'type_id'                    => $result->type_id,
            'name'                       => $result->name,
            'owner_id'                   => $result->owner ?? null,
            'race_id'                    => $result->race_id ?? null,
            'system_id'                  => $result->system_id,
            'reprocessing_efficiency'    => $result->reprocessing_efficiency,
            'reprocessing_stations_take' => $result->reprocessing_stations_take,
            'max_dockable_ship_volume'   => $result->max_dockable_ship_volume,
            'office_rental_cost'         => $result->office_rental_cost,
        ])->touch();

        Location::firstOrCreate([
            'location_id' => $location_id,
        ], [
            'locatable_id' => $location_id,
            'locatable_type' => Station::class,
        ]);

    }
}
