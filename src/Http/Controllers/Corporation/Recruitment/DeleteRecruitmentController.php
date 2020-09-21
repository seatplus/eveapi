<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment;


use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;

class DeleteRecruitmentController extends Controller
{
    public function __invoke(int $corporation_id)
    {
        Enlistments::where('corporation_id',$corporation_id)->delete();

        return back()->with('success', 'corporation is closed for recruitment');
    }

}
