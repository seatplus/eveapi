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

namespace Seatplus\Eveapi\Jobs\Alliances;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Actions\HasPathValuesInterface;
use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class AllianceInfoJob extends NewEsiBase implements HasPathValuesInterface
{
    public array $path_values;

    public function __construct(
        public JobContainer $job_container
    ) {
        parent::__construct($job_container);

        $this->setPathValues([
            'alliance_id' => $job_container->getAllianceId(),
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
            (new ThrottlesExceptionsWithRedis(80,5))
                ->by($this->uniqueId())
                ->when(fn() => !$this->isEsiRateLimited())
                ->backoff(5)
        ];
    }

    public function tags(): array
    {
        return [
            'alliance',
            'alliance_id: ' . $this->getAllianceId(),
            'info',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    public function handle(): void
    {
        if ($this->batching() && $this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        AllianceInfo::firstOrNew(['alliance_id' => $this->getAllianceId()])->fill([
            'creator_corporation_id' => $response->creator_corporation_id,
            'creator_id' => $response->creator_id,
            'date_founded' => carbon($response->date_founded),
            'executor_corporation_id' => $response->optional('executor_corporation_id'),
            'faction_id' => $response->optional('faction_id'),
            'name' => $response->name,
            'ticker' => $response->ticker,
        ])->save();
    }


    public function getPathValues(): array
    {
        return $this->path_values;
    }

    public function setPathValues(array $array): void
    {
        $this->path_values = $array;
    }

    public function getJobType(): string
    {
        return 'character';
    }

    public function getMethod(): string
    {
        return 'get';
    }

    public function getEndpoint(): string
    {
        return '/alliances/{alliance_id}/';
    }

    public function getVersion(): string
    {
        return 'v3';
    }
}
