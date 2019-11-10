<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Exception;

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
            return $next($job);

        $job->fail(new Exception('Refresh token is missing'));
    }
}
