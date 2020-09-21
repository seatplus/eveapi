<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment;


use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Http\Request\ApplicationRequest;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Services\GetOwnedIds;

class DeleteUserApplicationController extends Controller
{
    public function __invoke()
    {

        auth()->user()->application()->delete();

        return back()->with('success', 'Application deleted');
    }

}
