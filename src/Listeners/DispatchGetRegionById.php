<?php


namespace Seatplus\Eveapi\Listeners;


use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseRegionByRegionIdJob;


class DispatchGetRegionById
{
    public function handle(UniverseConstellationCreated $universe_constellation_created)
    {
        if($universe_constellation_created->constellation->region)
            return;

        $job = new ResolveUniverseRegionByRegionIdJob;
        $job->setRegionId($universe_constellation_created->constellation->region_id);

        dispatch($job)->onQueue('default');
    }

}
