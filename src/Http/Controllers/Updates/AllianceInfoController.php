<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;

class AllianceInfoController extends Controller
{
    public function update(Request $request)
    {

        $validatedData = $request->validate([
            'alliance_id' => 'required',
        ]);

        $job_container = new JobContainer([
            'alliance_id' => $request->alliance_id,
        ]);

        AllianceInfo::dispatch($job_container)->onQueue('default');

        return response('success', 200);

    }
}
