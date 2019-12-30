<?php


namespace Seatplus\Eveapi\Http\Controllers\Updates\Universe;


use Illuminate\Http\Request;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Universe\ResolvePublicStructureJob;
use Seatplus\Eveapi\Models\RefreshToken;

class PublicStructureController extends Controller
{

    public function update(Request $request)
    {

        $refresh_token = RefreshToken::all()->filter(function (RefreshToken $refresh_token) {
            return $refresh_token->hasScope('esi-universe.read_structures.v1');
        })->random();

        $job_container = new JobContainer([
            'refresh_token' => $refresh_token,
        ]);

        dispatch(new ResolvePublicStructureJob($job_container))->onQueue('default');

        return response('successfully queued', 200);

    }

}
