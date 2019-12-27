<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

class StationChecker extends LocationChecker
{

    public function check(Location $location)
    {

        if (
            // if locatable exists and if locatable is of type Station
            ($location->exists && is_a($location->locatable, Station::class) && $location->updated_at < carbon()->subWeek())
            // or if location does not exist and id is between 60000000 and 64000000
            || (!$location->exists && $location->location_id > 60000000 && $location->location_id < 64000000)
        )
            (new ResolveUniverseStationByIdAction)->execute($location->location_id);

        $this->next($location);
    }
}
