<?php


namespace Seatplus\Eveapi\Http\Controllers\Alliance;


use Seatplus\Eveapi\Http\Resources\AllianceInfoResource;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class InfoController
{
    public function index()
    {

        //TODO limit query results to only alliance info the user has access to
        return AllianceInfoResource::collection(
            AllianceInfo::paginate()
        );
    }

}
