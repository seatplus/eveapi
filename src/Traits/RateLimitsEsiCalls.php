<?php

namespace Seatplus\Eveapi\Traits;

use Illuminate\Support\Facades\Redis;

/**
 * Trait RateLimitsEsiCalls.
 * @package Seat\Eveapi\Traits
 */
trait RateLimitsEsiCalls
{

    //TODO: refactor this to action!

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
