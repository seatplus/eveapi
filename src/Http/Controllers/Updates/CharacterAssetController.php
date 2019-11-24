<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\RefreshToken;

class CharacterAssetController extends Controller
{
    public function update(Request $request)
    {

        $validatedData = $request->validate([
            'character_id' => 'required',
        ]);

        //(new CharacterRoleAction)->execute(RefreshToken::find((int) $validatedData['character_id']));

        $job_container = new JobContainer([
            'refresh_token' => RefreshToken::find((int) $validatedData['character_id']),
        ]);

        CharacterRoleJob::dispatch($job_container)->onQueue('default');

        return response('success', 200);

    }
}
