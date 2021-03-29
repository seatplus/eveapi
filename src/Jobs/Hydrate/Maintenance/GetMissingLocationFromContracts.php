<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Illuminate\Database\Eloquent\Builder;

class GetMissingLocationFromContracts extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        Contract::query()
            ->where(function (Builder $query) {
                $query->whereNotNull('start_location_id')
                    ->whereDoesntHave('start_location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]));
            })
            ->orWhere(function (Builder $query) {
                $query->whereNotNull('end_location_id')
                    ->whereDoesntHave('end_location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]));
            })
            //->whereDoesntHave('start_location', fn ($query) => $query->whereNotNull('start_location_id'))
            //->orWhereDoesntHave('end_location', fn ($query) => $query->whereNotNull('end_location_id'))
            ->inRandomOrder()
            ->get()
            ->each(function ($contract) {
                $unknown_location_ids = collect();

                if (is_null($contract->start_location) || $this->isNotStationOrStructure($contract->start_location)) {
                    $unknown_location_ids->push($contract->start_location_id);
                }

                if (is_null($contract->end_location) || $this->isNotStationOrStructure($contract->end_location)) {
                    $unknown_location_ids->push($contract->end_location_id);
                }

                $refresh_token = RefreshToken::find($contract->issuer_id) ?? RefreshToken::find($contract->assignee_id);

                if (is_null($refresh_token)) {
                    return;
                }

                $unknown_location_ids
                    ->filter()
                    ->unique()
                    ->each(fn ($location_id) => ResolveLocationJob::dispatch($location_id, $refresh_token)->onQueue('high'));
            });
    }

    private function isNotStationOrStructure($location): bool
    {
        return ! (is_a($location, Structure::class) || is_a($location, Structure::class));
    }
}