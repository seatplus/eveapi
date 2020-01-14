<?php

namespace Seatplus\Eveapi\Listeners;

use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseConstellationByConstellationIdJob;

class DispatchGetConstellationById
{
    public function handle(UniverseSystemCreated $universe_system_created)
    {

        if($universe_system_created->system->constellation)
            return;

        $job = new ResolveUniverseConstellationByConstellationIdJob;
        $job->setConstellationId($universe_system_created->system->constellation_id);

        dispatch($job)->onQueue('default');
    }
}
