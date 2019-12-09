<?php


namespace Seatplus\Eveapi\Actions\Seatplus;


use Illuminate\Support\Collection;
use Seatplus\Eveapi\Models\Universe\Types;

class CacheMissingGroupIdsAction
{
    public function execute() : Collection
    {
        $unknown_type_ids = Types::whereDoesntHave('group')->pluck('group_id')->unique()->values();

        (new CreateOrUpdateMissingIdsCache('group_ids_to_resolve',$unknown_type_ids))->handle();

        return $unknown_type_ids;
    }

}
