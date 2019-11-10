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

        if($this->isEsiRateLimited())
            $job->fail(new Exception('Hitting Esi Rate Limit. Probably the scope is missing or refresh_token was deleted'));


        return $next($job);
    }
}
