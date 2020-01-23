<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation;


use Seatplus\Eveapi\Http\Resources\CorporationInfoResource;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class InfoController
{
    public function index()
    {
        return CorporationInfoResource::collection(
            CorporationInfo::paginate()
        );
    }

}
