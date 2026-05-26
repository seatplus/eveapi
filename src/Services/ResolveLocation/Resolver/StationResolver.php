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

namespace Seatplus\Eveapi\Services\ResolveLocation\Resolver;

use Exception;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class StationResolver implements ResolverInterface
{
    const int MIN_STATION_ID = 60_000_000;

    const int MAX_STATION_ID = 64_000_000;

    /**
     * @throws Exception
     */
    #[\Override]
    public function handle(Location $location): bool
    {

        if ($this->isStructure($location) || $this->isUpdatedStation($location)) {
            return false;
        }

        if ($this->isPotentialStation($location)) {
            $this->handlePotentialStation($location);

            return true;
        }

        return false;
    }

    protected function dispatchStationResolutionJob(Location $location): void
    {
        ResolveUniverseStationByIdJob::dispatchSync($location->location_id);
    }

    private function isStructure(Location $location): bool
    {
        return is_a($location->locatable, Structure::class);
    }

    private function isUpdatedStation(Location $location): bool
    {
        return is_a($location->locatable, Station::class) && $location->locatable->updated_at > carbon()->subWeek();
    }

    private function isPotentialStation(Location $location): bool
    {
        return $location->location_id > self::MIN_STATION_ID && $location->location_id < self::MAX_STATION_ID;
    }

    private function createLocation(Location $location): void
    {
        if (! $location->exists) {
            $location->save();
        }
    }

    /**
     * @throws Exception
     */
    public function handlePotentialStation(Location $location): void
    {
        // create location if it does not exist yet
        $this->createLocation($location);

        try {
            // dispatch job to resolve station
            $this->dispatchStationResolutionJob($location);

            // log success
            logger()->info("successfully resolved station with id {$location->location_id}");
        } catch (Exception $e) {
            // log error
            logger()->error("failed to resolve station with id {$location->location_id}");

            // report exception
            throw $e;
        }
    }
}
