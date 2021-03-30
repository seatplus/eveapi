<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Illuminate\Database\Eloquent\Builder;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class GetMissingTypesFromLocations extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $type_ids = Location::whereHasMorph(
            'locatable',
            [Station::class, Structure::class],
            function (Builder $query) {
                $query->whereDoesntHave('type')->addSelect('type_id');
            }
        )->with('locatable')->get()->map(function ($location) {
            return $location->locatable->type_id;
        })->unique()->values();

        $jobs = $type_ids->map(fn($id) => new ResolveUniverseTypeByIdJob($id));

        $this->batch()->add(
            $jobs->toArray()
        );
    }
}