<?php

namespace Seatplus\Eveapi\Actions\Seatplus;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class CacheMissingTypeIdsAction
{
    public function execute() : Collection
    {

        $unknown_type_ids = CharacterAsset::whereDoesntHave('type')->pluck('type_id')->unique()->values();

        $unknown_type_ids = $unknown_type_ids->merge($this->getMissingTypeIdsFromLocations());

        (new CreateOrUpdateMissingIdsCache('type_ids_to_resolve', $unknown_type_ids))->handle();

        return $unknown_type_ids;
    }

    private function getMissingTypeIdsFromLocations() : Collection
    {
        return Location::whereHasMorph(
            'locatable',
            [Station::class, Structure::class],
            function (Builder $query) {
                $query->whereDoesntHave('type')->addSelect('type_id');
            }
        )->with('locatable')->get()->map(function ($location) {
            return $location->locatable->type_id;
        });
    }
}
