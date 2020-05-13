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

namespace Seatplus\Eveapi\Jobs\Universe;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Location\AssetSafetyChecker;
use Seatplus\Eveapi\Actions\Location\StationChecker;
use Seatplus\Eveapi\Actions\Location\StructureChecker;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RateLimitedJobMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;

class ResolveLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken|null
     */
    public $refresh_token;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\AssetSafetyChecker
     */
    private $asset_safety_checker;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\StationChecker
     */
    private $station_checker;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\StructureChecker
     */
    private $structure_checker;

    /**
     * @var int
     */
    public $location_id;

    public function middleware(): array
    {

        $rate_limited_job_middleware = (new RateLimitedJobMiddleware)
            ->setKey((string) $this->location_id)
            ->setViaCharacterId($this->refresh_token->character_id)
            ->setDuration(7200);

        return [
            new RedisFunnelMiddleware,
            new HasRefreshTokenMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
            $rate_limited_job_middleware,
        ];
    }

    public function __construct(int $location_id, RefreshToken $refresh_token)
    {

        $this->location_id = $location_id;
        $this->refresh_token = $refresh_token;

        $this->asset_safety_checker = new AssetSafetyChecker;
        $this->station_checker = new StationChecker;
        $this->structure_checker = new StructureChecker($this->refresh_token);

        $this->asset_safety_checker->succeedWith($this->station_checker);
        $this->station_checker->succeedWith($this->structure_checker);

    }

    public function tags() {
        return [
            'location_resolve',
            'location_id:' . $this->location_id,
            'via character: ' . $this->refresh_token->character_id,
        ];
    }

    public function handle(): void
    {
        $location = Location::firstOrNew(['location_id' => $this->location_id]);

        $this->asset_safety_checker->check($location);
    }
}
