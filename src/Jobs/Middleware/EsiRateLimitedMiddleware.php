<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Exception;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class EsiRateLimitedMiddleware
{
    use RateLimitsEsiCalls;

    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {

        return $this->isEsiRateLimited()
            ? $job->fail(new Exception('Hitting Esi Rate Limit. Probably the scope is missing or refresh_token was deleted'))
            : $next($job);
    }
}
