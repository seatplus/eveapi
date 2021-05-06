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

namespace Seatplus\Eveapi\Jobs\Corporation;

use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Traits\HasPathValues;

class CorporationInfoJob extends NewEsiBase implements HasPathValuesInterface
{
    use HasPathValues;

    public function __construct(?JobContainer $job_container = null)
    {
        $this->setJobType('public');
        parent::__construct($job_container);

        $this->setMethod('get');
        $this->setEndpoint('/corporations/{corporation_id}/');
        $this->setVersion('v4');

        $this->setPathValues([
            'corporation_id' => $this->corporation_id,
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
            (new ThrottlesExceptionsWithRedis(80, 5))
                ->by($this->uniqueId())
                ->when(fn () => ! $this->isEsiRateLimited())
                ->backoff(5),
        ];
    }

    public function tags(): array
    {
        return [
            'corporation',
            'info',
            'corporation_id:' . $this->corporation_id,
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        CorporationInfo::firstOrNew(['corporation_id' => $this->corporation_id])->fill([
            'ticker'          => $response->ticker,
            'name'            => $response->name,
            'member_count'    => $response->member_count,
            'ceo_id'          => $response->ceo_id,
            'creator_id'      => $response->creator_id,
            'tax_rate'        => $response->tax_rate,
            'alliance_id'     => $response->optional('alliance_id'),
            'date_founded'    => property_exists($response, 'date_founded') ?
                carbon($response->date_founded) : null,
            'description'     => $response->optional('description'),
            'faction_id'      => $response->optional('faction_id'),
            'home_station_id' => $response->optional('home_station_id'),
            'shares'          => $response->optional('shares'),
            'url'             => $response->optional('url'),
            'war_eligible'    => $response->optional('war_eligible'),
        ])->save();
    }
}
