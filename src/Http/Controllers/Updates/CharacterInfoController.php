<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;

class CharacterInfoController extends Controller
{
    public function update(Request $request)
    {

        $validatedData = $request->validate([
            'character_id' => 'required',
        ]);

        $job_container = new JobContainer([
            'character_id' => $request->character_id,
        ]);

        CharacterInfo::dispatch($job_container)->onQueue('default');

        return response('success', 200);

    }
}
