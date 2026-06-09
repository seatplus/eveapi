<?php

declare(strict_types=1);

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
    public function handle(int $locationId, Collection $tracking): ?RefreshToken
    {

        $corporationMemberTrackingQuery = CorporationMemberTracking::query()
            ->where('location_id', $locationId);

        $refreshToken = app(Pipeline::class)
            ->send($corporationMemberTrackingQuery)
            ->through([
                $this->findMemberTokenWithStructureScope($tracking),
                $this->findDirectorToken(),
            ])
            ->thenReturn();

        return $refreshToken;
    }

    private function findMemberTokenWithStructureScope(Collection $tracking): \Closure
    {
        $characterIdsToIgnore = $tracking->pluck('character_id');

        return function (Builder $query, \Closure $next) use ($characterIdsToIgnore) {
            $refreshToken = $query
                ->whereNotIn('character_id', $characterIdsToIgnore)
                ->whereHas('character.refreshToken')
                ->inRandomOrder()
                ->get()
                ->map(fn (CorporationMemberTracking $memberTracking) => $memberTracking->character->refreshToken)
                ->unique()
                // filter refresh token that has scope esi-universe.read_structures.v1
                ->filter(fn (RefreshToken $refreshToken) => $refreshToken->hasScope('esi-universe.read_structures.v1'))
                ->first();

            if (! is_null($refreshToken)) {
                return $refreshToken;
            }

            return $next($query);
        };
    }

    private function findDirectorToken(): \Closure
    {
        return function (Builder $query) {
            $refreshToken = $query
                ->inRandomOrder()
                ->pluck('corporation_id')
                ->unique()
                ->map(function (int $corporationId) {

                    $service = new FindCorporationRefreshToken;

                    return $service($corporationId, 'esi-corporations.track_members.v1', 'Director');
                })
                ->filter()
                ->first();

            return $refreshToken;
        };
    }
}
