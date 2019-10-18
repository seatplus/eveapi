<?php

namespace Seatplus\Eveapi\Actions\Jobs\Character;

use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Actions\Alliances\AllianceInfoAction;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class InfoAction
{
    public function execute(int $character_id)
    {

        if ($esi_response->isCachedLoad()) return;

        CharacterInfo::firstOrNew(['character_id' => $character_id])->fill([
            'name'            => $esi_response->name,
            'description'     => $esi_response->optional('description'),
            'corporation_id'  => $esi_response->corporation_id,
            'alliance_id'     => $esi_response->optional('alliance_id'),
            'birthday'        => $esi_response->birthday,
            'gender'          => $esi_response->gender,
            'race_id'         => $esi_response->race_id,
            'bloodline_id'    => $esi_response->bloodline_id,
            'ancestry_id'    => $esi_response->optional('ancestry_id'),
            'security_status' => $esi_response->optional('security_status'),
            'faction_id'      => $esi_response->optional('faction_id'),
        ])->save();

        if (! empty($esi_response->optional('alliance_id')))
        {
            $alliance_info_action = new AllianceInfoAction();
            $alliance_info_action->onQueue()->execute($esi_response->alliance_id);
        }

    }
}
