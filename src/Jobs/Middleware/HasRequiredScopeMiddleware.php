<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Exception;

class HasRequiredScopeMiddleware
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

        if(in_array($job->getActionClass()->required_scope, $job->refresh_token->scopes))
            return $next($job);


        $job->fail(new Exception('refresh_token misses required scope: ' . $job->getRequiredScope()));
    }
}
