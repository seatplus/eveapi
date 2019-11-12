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

        $required_socpe = $job->getActionClass()->required_scope;

        if(in_array($required_socpe, $job->refresh_token->scopes))
            return $next($job);

        $job->fail(new Exception('refresh_token misses required scope: ' . $required_socpe));
    }
}
