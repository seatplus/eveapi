<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment;


use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;

class GetEnlistmentsController extends Controller
{
    public function __invoke()
    {
        return Enlistments::with('corporation', 'corporation.alliance')->get()->toJson();
    }

}
