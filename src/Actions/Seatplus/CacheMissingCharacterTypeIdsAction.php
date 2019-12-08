<?php

namespace Seatplus\Eveapi\Actions\Seatplus;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class CacheMissingCharacterTypeIdsAction
{
    public function execute() : Collection
    {

        $unknown_type_ids = CharacterAsset::whereDoesntHave('type')->pluck('type_id')->unique()->values();

        (new CreateOrUpdateMissingTypeIdCache($unknown_type_ids))->handle();

        return $unknown_type_ids;
    }
}
