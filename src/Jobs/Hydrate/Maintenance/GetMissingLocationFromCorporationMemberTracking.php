<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class GetMissingLocationFromCorporationMemberTracking extends HydrateMaintenanceBase
{

    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $find_corporation_refresh_token = new FindCorporationRefreshToken;

        CorporationMemberTracking::whereDoesntHave('location', fn ($query) => $query->whereHasMorph('locatable', [Structure::class, Station::class]))
            ->inRandomOrder()
            ->get()
            ->map(function (CorporationMemberTracking $corporation_member_tracking) use ($find_corporation_refresh_token) {
                $refresh_token = $find_corporation_refresh_token($corporation_member_tracking->corporation_id, 'esi-corporations.track_members.v1', 'Director') ?? RefreshToken::find($corporation_member_tracking->character_id);

                if ($refresh_token) {
                    $this->batch()->add([
                        new ResolveLocationJob($corporation_member_tracking->location_id, $refresh_token)
                    ]);
                }
                return false;
            });
    }
}