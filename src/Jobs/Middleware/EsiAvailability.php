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

        $staus = (new GetEsiStatusAction())->execute();

        if($staus === 'ok')
            $next($job);
    }

}