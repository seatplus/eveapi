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

namespace Seatplus\Eveapi\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class RateLimitedJobMiddleware
{
    private string $key;

    private int $duration;

    private int $viaCharacterId;

    /**
     * Process the queued job.
     *
     * @param mixed    $job
     * @param \Closure $next
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function handle($job, Closure $next)
    {
        $redisKey = sprintf('%s:%s', $this->key, $this->viaCharacterId);

        Redis::throttle($redisKey)
            ->block(0)->allow(1)->every($this->duration)
            ->then(function () use ($job, $next) {

                return $next($job);
                }, function () use ($job) {

                $job->delete();
            });
    }

    /**
     * @param string $key
     *
     * @return RateLimitedJobMiddleware
     */
    public function setKey(string $key): RateLimitedJobMiddleware
    {

        $this->key = $key;

        return $this;
    }

    /**
     * @param int $duration
     *
     * @return RateLimitedJobMiddleware
     */
    public function setDuration(int $duration): RateLimitedJobMiddleware
    {

        $this->duration = $duration;

        return $this;
    }

    /**
     * @param int $viaCharacterId
     *
     * @return RateLimitedJobMiddleware
     */
    public function setViaCharacterId(int $viaCharacterId): RateLimitedJobMiddleware
    {

        $this->viaCharacterId = $viaCharacterId;

        return $this;
    }
}
