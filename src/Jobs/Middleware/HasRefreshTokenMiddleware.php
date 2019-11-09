<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Mockery\Exception;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

class HasRefreshTokenMiddleware
{

    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {

        if($job->refresh_token)
            $next($job);

        $job->fail(new Exception('Refresh token is missing'));
    }
}
