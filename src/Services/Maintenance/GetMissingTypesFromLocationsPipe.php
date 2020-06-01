<?php


namespace Seatplus\Eveapi\Services\Maintenance;


use Closure;
use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class GetMissingTypesFromLocationsPipe
{
    public function handle($payload, Closure $next)
    {

        $type_ids = Location::whereHasMorph(
            'locatable',
            [Station::class, Structure::class],
            function (Builder $query) {
                $query->whereDoesntHave('type')->addSelect('type_id');
            }
        )->with('locatable')->get()->map(function ($location) {
            return $location->locatable->type_id;
        })->unique()->values();

        if($type_ids->isNotEmpty())
            (new CreateOrUpdateMissingIdsCache('type_ids_to_resolve', $type_ids))->handle();

        ResolveUniverseTypesByTypeIdJob::dispatch()->onQueue('high');

        return $next($payload);
    }

}
