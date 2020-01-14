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

namespace Seatplus\Eveapi\Traits;

use Illuminate\Support\Facades\Redis;

/**
 * Trait RateLimitsEsiCalls.
 * @package Seat\Eveapi\Traits
 */
trait RateLimitsEsiCalls
{

    /**
     * @var string
     */
    protected $ratelimit_key = 'esiratelimit';

    /**
     * @var int
     */
    protected $ratelimit = 80;

    /**
     * Number of minutes to consider the rate limit
     * for errors.
     *
     * @var int
     */
    protected $ratelimit_duration = 5;

    /**
     * @return bool
     * @throws \Exception
     */
    public function isEsiRateLimited(): bool
    {

        if (cache()->get($this->ratelimit_key) < $this->ratelimit)
            return false;

        return true;
    }

    /**
     * @param int $amount
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function incrementEsiRateLimit(int $amount = 1)
    {

        if ($this->getRateLimitKeyTtl() > 3) {

            cache()->increment($this->ratelimit_key, $amount);

        } else {

            cache()->set($this->ratelimit_key, $amount, carbon('now')
                ->addMinutes($this->ratelimit_duration));
        }
    }

    /**
     * @return mixed
     */
    public function getRateLimitKeyTtl()
    {

        return Redis::ttl('seat:' . $this->ratelimit_key);
    }
}
