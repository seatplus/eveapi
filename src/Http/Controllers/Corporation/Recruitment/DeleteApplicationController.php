<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment;


use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Http\Request\ApplicationRequest;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Services\GetOwnedIds;

class DeleteApplicationController extends Controller
{
    public function __invoke(int $character_id)
    {

        $user_owns_character_id = in_array($character_id, (new GetOwnedIds)->execute());

        abort_unless($user_owns_character_id, 403, 'submitted character_id does not belong to user');

        CharacterInfo::find($character_id)->application()->delete();

        return back()->with('success', 'Application deleted');
    }

}
