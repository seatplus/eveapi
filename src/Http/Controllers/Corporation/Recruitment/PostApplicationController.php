<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment;


use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Http\Request\ApplicationRequest;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Services\GetOwnedIds;

class PostApplicationController extends Controller
{
    public function __invoke(ApplicationRequest $application_request)
    {

        $application_request->get('character_id', false) ? $this->handleCharacterApplication($application_request) : $this->handleUserApplication($application_request);

        return back()->with('success', 'Application submitted');
    }

    private function handleUserApplication(ApplicationRequest $application_request) : void
    {
        auth()->user()->application()->create(['corporation_id' => $application_request->get('corporation_id')]);
    }

    private function handleCharacterApplication(ApplicationRequest $application_request) : void
    {

        $character_id = $application_request->get('character_id');

        $user_owns_character_id = in_array($character_id, (new GetOwnedIds)->execute());

        abort_unless($user_owns_character_id, 403, 'submitted character_id does not belong to user');

        CharacterInfo::find($character_id)->application()->create(['corporation_id' => $application_request->get('corporation_id')]);
    }

}
