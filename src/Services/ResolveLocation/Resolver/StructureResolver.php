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
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStructureByIdJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class StructureResolver implements ResolverInterface
{
    private StructureRefreshTokenFinder $tokenService;

    public ?RefreshToken $refreshToken = null;

    public function __construct(StructureRefreshTokenFinder|RefreshToken|null $args = null)
    {

        // if args is StructureRefreshTokenFinder, set it as tokenService
        if ($args instanceof StructureRefreshTokenFinder) {
            $this->tokenService = $args;

            return;
        }

        // if args is RefreshToken or null, set it as refreshToken
        if ($args instanceof RefreshToken) {
            // set it as refreshToken
            $this->refreshToken = $args;
        }

        $this->tokenService = new StructureRefreshTokenFinder($this->refreshToken);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function handle(Location $location): bool
    {
        if ($this->isStation($location) || $this->isUpdatedStructure($location)) {
            return false;
        }

        if ($this->isPotentialStructure($location)) {

            // create location if it does not exist yet
            $this->createLocation($location);

            // find valid refresh token for structure
            $this->findValidRefreshToken($location);

            if ($this->hasRefreshToken()) {
                $this->resolveStructure($location);

                return true;
            }
        }

        return false;
    }

    private function dispatchStructureResolutionJob(Location $location): void
    {
        ResolveUniverseStructureByIdJob::dispatchSync($this->refreshToken->character_id, $location->location_id);
    }

    private function isStation(Location $location): bool
    {
        return is_a($location->locatable, Station::class);
    }

    private function isUpdatedStructure(Location $location): bool
    {
        return is_a($location->locatable, Structure::class) && $location->locatable->updated_at > carbon()->subWeek();
    }

    private function isPotentialStructure(Location $location): bool
    {
        return $location->location_id >= 100_000_000;
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
    public function resolveStructure(Location $location): void
    {

        try {
            // dispatch job to resolve structure
            $this->dispatchStructureResolutionJob($location);

            // mark token as resolved
            $this->tokenService->markAsResolved();

            // log success
            logger()->info("successfully resolved structure with id {$location->location_id} using refresh_token of {$this->refreshToken->character->name}");
        } catch (Exception $e) {
            // mark token as failed
            $this->tokenService->markAsFailed();

            // log error
            logger()->error("failed to resolve structure with id {$location->location_id} using refresh_token of {$this->refreshToken->character->name}");
            // throw exception
            throw $e;
        }
    }

    public function findValidRefreshToken(Location $location): void
    {
        // find valid token for structure
        $this->refreshToken = $this->tokenService->findValidToken($location->location_id);

        // if no valid token found, skip and log
        if (! $this->refreshToken) {
            logger()->warning("no valid token found for structure with id {$location->location_id}");
        }
    }

    private function hasRefreshToken(): bool
    {
        return $this->refreshToken instanceof RefreshToken;
    }
}
