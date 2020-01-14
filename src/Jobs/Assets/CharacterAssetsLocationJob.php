<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 seatplus
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

namespace Seatplus\Eveapi\Jobs\Assets;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Jobs\Assets\CharacterAssetsLocationAction;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;

class CharacterAssetsLocationJob implements ShouldQueue
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
     * @var \Seatplus\Eveapi\Containers\JobContainer
     */
    private $job_container;

    public function __construct(JobContainer $job_container)
    {
        $this->refresh_token = $job_container->getRefreshToken();
        $this->job_container = $job_container;
    }

    public function middleware(): array
    {
        return [
            new RedisFunnelMiddleware,
            new HasRefreshTokenMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags() {
        return [
            'character',
            'character_id: ' . $this->refresh_token->character_id,
            'assets',
            'location',
        ];
    }

    public function handle(): void
    {
        $action = new CharacterAssetsLocationAction($this->refresh_token);
        $action->buildLocationIds();

        // Log the location ids
        logger()->debug('location_ids to be resolved: ' . $action->getLocationIds()->implode(', '));

        $action->execute();
    }
}
