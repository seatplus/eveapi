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

namespace Seatplus\Eveapi\Services\ResolveLocation;

use Closure;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class ResolveStationPipe
{
    public function __construct(
        public int $location_id
    ) {
    }

    public function handle(ResolveLocationDTO $payload, Closure $next)
    {

        // if structure just return early
        if (is_a($payload->location->locatable, Structure::class)) {
            return $next($payload);
        }

        // if location is station and last update is greater then a week don't bother no longer
        if (is_a($payload->location->locatable, Station::class) && $payload->location->locatable->updated_at > carbon()->subWeek()) {
            return $next($payload);
        }

        if ($this->location_id > 60_000_000 && $this->location_id < 64_000_000) {
            $this->getStation();
            $payload->log_message = sprintf('successfully resolved station with id %s', $this->location_id);
        }

        return $next($payload);
    }

    private function getStation()
    {
        ResolveUniverseStationByIdJob::dispatch($this->location_id)->onQueue('high');
        //(new ResolveUniverseStationByIdAction)->execute($this->location_id);
    }
}
