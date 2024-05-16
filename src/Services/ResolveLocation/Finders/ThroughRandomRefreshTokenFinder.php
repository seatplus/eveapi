<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughRandomRefreshTokenFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {

        $character_ids_to_ignore = $tracings->pluck('character_id');

        return RefreshToken::query()
            ->whereNotIn('character_id', $character_ids_to_ignore)
            ->inRandomOrder()
            ->cursor()
            ->firstWhere(fn ($refresh_token) => $refresh_token->hasScope('esi-universe.read_structures.v1'));
    }
}
