<?php

namespace Seatplus\Eveapi\Jobs\Middleware;

use Seatplus\Eveapi\Actions\Esi\GetEsiStatusAction;
use Seatplus\Eveapi\Exceptions\NPCCorporationCheckException;

class NPCCorporationMiddleware
{
    /**
     * Process the queued job. If corporation_id is non NPC.
     * Please note: do only include this middleware in jobs that address non-public endpoints.
     * It is totally fine to pull public NPC infos.
     *
     * @param mixed    $job
     * @param callable $next
     *
     * @return mixed
     * @throws \Seatplus\Eveapi\Exceptions\NPCCorporationCheckException
     */
    public function handle($job, $next)
    {

        if(is_null($job->corporation_id))
            throw new NPCCorporationCheckException('Missing corporation_id, are you sure you are using this Middleware in a corporation job?');


        // If corporation is NPC delete the job, as we are not able to pull date for those
        if((1000000 <= $job->corporation_id) && ($job->corporation_id <= 2000000))
            $job->delete();

        $next($job);


    }
}
