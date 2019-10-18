<?php

namespace Seatplus\Eveapi\Http\Controllers\Jobs\Character;

use Illuminate\Http\Request;
use Seatplus\Eveapi\Actions\Alliances\AllianceInfoAction;
use Seatplus\Eveapi\Actions\Jobs\Character\InfoAction;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Jobs\Character\Info;

class InfoController extends Controller
{
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'character_id' => 'required'
        ]);

        (new Info())->setCharacterId(1234);


        $alliance_info_action = new AllianceInfoAction();
        $data = $alliance_info_action->onQueue('default')->execute($validatedData['character_id']);

        dd($data);

    }

}
