<?php


namespace Seatplus\Eveapi\Jobs\Middleware;


use Closure;
use Illuminate\Support\Facades\Redis;
use Seatplus\Eveapi\Jobs\EsiBase;

class RateLimitedJobMiddleware
{
    private string $key;

    private int $duration;

    /**
     * Process the queued job.
     *
     * @param mixed    $job
     * @param \Closure $next
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function handle(EsiBase $job, Closure $next)
    {
        $redisKey = sprintf('%s:%s', $this->key, $job->character_id);

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

}
