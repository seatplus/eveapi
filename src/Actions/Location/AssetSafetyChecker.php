<?php

namespace Seatplus\Eveapi\Actions\Location;

use Seatplus\Eveapi\Models\Universe\Location;

class AssetSafetyChecker extends LocationChecker
{
    public function check(Location $location)
    {

        if ($location->location_id === 2004)
            return;

        $this->next($location);
    }
}
