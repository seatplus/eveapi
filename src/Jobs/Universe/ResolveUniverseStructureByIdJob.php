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

use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class ResolveUniverseStructureByIdJob extends NewEsiBase implements HasPathValuesInterface, HasRequiredScopeInterface
{
    use HasPathValues;
    use HasRequiredScopes;

    public int $maxExceptions = 1;

    public function __construct(
        RefreshToken $refresh_token,
        public int $location_id
    ) {
        $this->setRefreshToken($refresh_token);

        $this->setMethod('get');
        $this->setEndpoint('/universe/structures/{structure_id}/');
        $this->setVersion('v2');

        $this->setRequiredScope('esi-universe.read_structures.v1');

        $this->setPathValues([
            'structure_id' => $this->location_id,
        ]);
    }

    public function tags(): array
    {
        return [
            'resolve',
            'universe',
            'structure',
            'location_id:' . $this->location_id,
        ];
    }

    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            // This is very likely throwing errors if user is not on acl. In order to not getting blocked by esi rate limit only use half of allowed errors
            (new ThrottlesExceptionsWithRedis($this->getRatelimit() / 2, 5))
                 ->by('esiratelimit')
                 ->backoff(5),
        ];
    }

    public function handle(): void
    {
        $result = $this->retrieve();

        if ($result->isCachedLoad()) {
            return;
        }

        Structure::updateOrCreate([
            'structure_id' => $this->location_id,
        ], [
            'name' => $result->name,
            'owner_id' => $result->owner_id,
            'solar_system_id' => $result->solar_system_id,
            'type_id' => $result->type_id ?? null,
        ])->touch();

        Location::updateOrCreate([
            'location_id' => $this->location_id,
        ], [
            'locatable_id' => $this->location_id,
            'locatable_type' => Structure::class,
        ]);
    }

    public function failed($exception)
    {

        if ($exception instanceof MaxAttemptsExceededException) {

            $this->delete();
            logger()->info('deleted job because MaxAttemptsException');
            return;
        }


        if ($exception?->getOriginalException()?->getResponse()?->getReasonPhrase() === 'Forbidden') {
            logger()->info('Received Forbidden, going to delete the job');
            $this->job->delete();
            return;
        }

        $this->delete();
    }

}
