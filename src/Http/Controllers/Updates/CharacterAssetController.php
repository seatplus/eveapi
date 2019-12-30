<?php

namespace Seatplus\Eveapi\Http\Controllers\Updates;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsLocationJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseCategoriesByCategoryIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseGroupsByGroupIdJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolvePublicStructureJob;
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

        CharacterAssetJob::withChain([
            new CharacterAssetsLocationJob($job_container),
            new ResolveUniverseTypesByTypeIdJob,
            new ResolveUniverseGroupsByGroupIdJob,
            new ResolveUniverseCategoriesByCategoryIdJob,
        ])->dispatch($job_container)->onQueue('default');

        return response('successfully queued', 200);

    }
}
