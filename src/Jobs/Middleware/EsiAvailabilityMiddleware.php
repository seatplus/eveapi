<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Exception;
use Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction;

class EsiAvailabilityMiddleware
{
    public $status;

    public function __construct()
    {
        $this->status = (new GetEsiStatusAction)->execute();
    }

    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {

        return $this->status === 'ok'
            ? $next($job)
            : $job->fail(new Exception('Esi appears to be down'));

        //TODO: introduce release for 15min in case of DT

    }
}
