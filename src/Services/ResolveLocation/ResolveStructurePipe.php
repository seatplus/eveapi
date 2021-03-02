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
use Seatplus\Eveapi\Actions\Location\ResolveUniverseStructureByIdAction;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class ResolveStructurePipe
{
    public function __construct(
        public int $location_id,
        public RefreshToken $refreshToken
    ) {
    }

    public function handle(ResolveLocationDTO $payload, Closure $next)
    {
        // if station just return early
        if (is_a($payload->location->locatable, Station::class)) {
            return $next($payload);
        }

        // if location is structure and last update is greater then a week don't bother no longer
        if (is_a($payload->location->locatable, Structure::class) && $payload->location->locatable->updated_at > carbon()->subWeek()) {
            return $next($payload);
        }

        // if location_id is a potential structure_id ( >= 100000000)
        if ($this->location_id >= 100_000_000) {
            $this->getStructure();
            $payload->log_message = sprintf('successfully resolved structure with id %s using refresh_token of %s',
                $this->location_id, $this->refreshToken->character->name
            );
        }

        return $next($payload);
    }

    private function getStructure()
    {
        $action = new ResolveUniverseStructureByIdAction($this->refreshToken);

        throw_unless(in_array($action->getRequiredScope(), $this->refreshToken->scopes), 'Trying to resolve a structure with a refresh token that is lacking the necessairy code');

        $action->execute($this->location_id);
    }
}
