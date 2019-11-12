<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Exception;
use Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction;

class EsiAvailabilityMiddleware
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

        $status = (new GetEsiStatusAction())->execute();

        $status === 'ok'
            ? $next($job)
            : $job->fail(new Exception('Esi appears to be down'));

        //TODO: introduce release for 15min in case of DT


    }
}
