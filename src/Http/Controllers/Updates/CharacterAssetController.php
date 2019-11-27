<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetController extends Controller
{
    public function update(Request $request)
    {

        $validatedData = $request->validate([
            'character_id' => 'required',
        ]);

        $job_container = new JobContainer([
            'refresh_token' => RefreshToken::find((int) $validatedData['character_id']),
        ]);

        CharacterAssetJob::dispatch($job_container)->onQueue('default');

        return response('success', 200);

    }
}
