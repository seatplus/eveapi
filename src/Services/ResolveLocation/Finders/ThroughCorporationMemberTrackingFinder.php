<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pipeline\Pipeline;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class ThroughCorporationMemberTrackingFinder implements FinderInterface
{
    #[\Override]
    public function handle(int $location_id, Collection $tracking): ?RefreshToken
    {

        $corporation_member_tracking_query = CorporationMemberTracking::query()
            ->where('location_id', $location_id);

        $refresh_token = app(Pipeline::class)
            ->send($corporation_member_tracking_query)
            ->through([
                $this->findMemberTokenWithStructureScope($tracking),
                $this->findDirectorToken(),
            ])
            ->thenReturn();

        return $refresh_token;
    }

    private function findMemberTokenWithStructureScope(Collection $tracking): \Closure
    {
        $character_ids_to_ignore = $tracking->pluck('character_id');

        return function (Builder $query, \Closure $next) use ($character_ids_to_ignore) {
            $refresh_token = $query
                ->whereNotIn('character_id', $character_ids_to_ignore)
                ->whereHas('character.refresh_token')
                ->inRandomOrder()
                ->get()
                ->map(fn (CorporationMemberTracking $member_tracking) => $member_tracking->character->refresh_token)
                ->unique()
                // filter refresh token that has scope esi-universe.read_structures.v1
                ->filter(fn (RefreshToken $refresh_token) => $refresh_token->hasScope('esi-universe.read_structures.v1'))
                ->first();

            if ($refresh_token) {
                return $refresh_token;
            }

            return $next($query);
        };
    }

    private function findDirectorToken(): \Closure
    {
        return function (Builder $query) {
            $refresh_token = $query
                ->inRandomOrder()
                ->pluck('corporation_id')
                ->unique()
                ->map(function (int $corporation_id) {

                    $service = new FindCorporationRefreshToken;

                    return $service($corporation_id, 'esi-corporations.track_members.v1', 'Director');
                })
                ->filter()
                ->first();

            return $refresh_token;
        };
    }
}
