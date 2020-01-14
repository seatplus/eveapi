<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates\Universe;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Http\Resources\CharacterAsset;
use Seatplus\Eveapi\Jobs\Universe\ResolvePublicStructureJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;

class SystemController extends Controller
{
    public function update()
    {

        $system_ids = Location::all()->map(function (Location $location) {
            return $location->locatable->system_id ?? $location->locatable->solar_system_id;
        })->unique()->random(3);

        $job = new ResolveUniverseSystemBySystemIdJob;

        foreach ($system_ids as $system_id)
            dispatch($job->setSystemId($system_id))->onQueue('default');

        return response('successfully queued', 200);

    }
}
