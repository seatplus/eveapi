<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction;

class EsiAvailability
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

        if($status === 'ok')
            $next($job);
    }
}
