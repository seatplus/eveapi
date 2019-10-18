<?php

namespace Seatplus\Eveapi\Http\Controllers\Jobs\Character;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Character\Info;

class InfoController extends Controller
{
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'character_id' => 'required'
        ]);


        $job_container = new JobContainer([
            'character_id' => $request->character_id
        ]);

        Info::dispatch($job_container)->onQueue('default');

        return response('success', 200);

        //(new Info($job_container))->handle();

    }

}
