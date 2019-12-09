<?php

namespace Seatplus\Eveapi\Actions\Seatplus;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Models\Universe\Groups;

class CacheMissingCategoryIdsAction
{
    public function execute() : Collection
    {
        $unknown_type_ids = Groups::whereDoesntHave('category')->pluck('category_id')->unique()->values();

        (new CreateOrUpdateMissingIdsCache('category_ids_to_resolve', $unknown_type_ids))->handle();

        return $unknown_type_ids;
    }
}
