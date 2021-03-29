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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequestBodyInterface;
use Seatplus\Eveapi\Esi\Jobs\Universe\ResolveUniverseGroupsByGroupIdAction;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;
use Seatplus\Eveapi\Jobs\NewEsiBase;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequestBody;

class ResolveUniverseGroupByIdJob extends NewEsiBase implements HasPathValuesInterface, HasRequestBodyInterface
{
    use HasPathValues, HasRequestBody;

    public function __construct(private int $group_id)
    {
        $this->setJobType('public');
        parent::__construct();

        $this->setMethod('get');
        $this->setEndpoint('/universe/groups/{group_id}/');
        $this->setVersion('v1');

        $this->setPathValues([
            'group_id' => $group_id,
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
            'group',
            'information',
            sprintf('group_id:%s', $this->group_id),
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() : void
    {

        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        Group::firstOrCreate(
            ['group_id' => $response->group_id],
            [
                'category_id' => $response->category_id,
                'name' => $response->name,
                'published' => $response->published,
            ]
        );
    }
}