<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;
use Seatplus\Eveapi\Jobs\Corporation\CorporationInfoJob;

class CorporationInfoController extends Controller
{
    public function update(Request $request)
    {

        $validatedData = $request->validate([
            'corporation_id' => 'required',
        ]);

        $job_container = new JobContainer([
            'corporation_id' => $request->corporation_id,
        ]);

        CorporationInfoJob::dispatch($job_container)->onQueue('default');

        return response('success', 200);

    }
}
