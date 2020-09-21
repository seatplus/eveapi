<?php


namespace Seatplus\Eveapi\Http\Controllers\Corporation\Recruitment;


use Illuminate\Support\Facades\Redirect;
use Seatplus\Eveapi\Http\Controllers\Controller;
use Seatplus\Eveapi\Http\Request\CreateOpenRecruitmentRequest;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;

class PostCreateOpenRecruitmentController extends Controller
{
    public function __invoke(CreateOpenRecruitmentRequest $request)
    {

        $enlistment = Enlistments::updateOrCreate(
            ['corporation_id' => $request->get('corporation_id')],
            ['type' => $request->get('type')]
        );

        return redirect()->back()->with('success', 'enlistment created');
    }

}
