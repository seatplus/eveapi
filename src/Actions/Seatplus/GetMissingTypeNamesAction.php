<?php


namespace Seatplus\Eveapi\Actions\Seatplus;


use Seatplus\Eveapi\Jobs\Seatplus\GetMissingTypeNamesJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class GetMissingTypeNamesAction
{
    public function execute()
    {
        $unknown_types = CharacterAsset::whereDoesntHave('type')->get();

        $unknown_type_ids = $unknown_types->pluck('type_id')->unique();

        (new CreateOrUpdateMissingTypeIdCache($unknown_type_ids))->handle();

        GetMissingTypeNamesJob::dispatch()->onQueue('default');
    }

}
