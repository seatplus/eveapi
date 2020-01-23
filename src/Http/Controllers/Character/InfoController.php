<?php


namespace Seatplus\Eveapi\Http\Controllers\Character;


use Seatplus\Eveapi\Http\Resources\CharacterInfoResource;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

class InfoController
{
    public function index()
    {
        return CharacterInfoResource::collection(
            CharacterInfo::paginate()
        );
    }

}
