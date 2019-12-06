<?php


namespace Seatplus\Eveapi\Actions\Seatplus;


use Seatplus\Eveapi\Jobs\Seatplus\GetMissingTypeNamesJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class GetMissingTypeNamesAction
{
    public function execute()
    {

        $unknown_type_ids = CharacterAsset::whereDoesntHave('type')->pluck('type_id')->unique()->values();

        (new CreateOrUpdateMissingTypeIdCache($unknown_type_ids))->handle();

        GetMissingTypeNamesJob::dispatch()->onQueue('default');
    }

}
