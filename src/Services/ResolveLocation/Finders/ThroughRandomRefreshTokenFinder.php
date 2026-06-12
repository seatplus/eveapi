<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughRandomRefreshTokenFinder implements FinderInterface
{
    #[\Override]
    public function handle(int $locationId, Collection $tracings): ?RefreshToken
    {

        $characterIdsToIgnore = $tracings->pluck('character_id');

        return RefreshToken::query()
            ->whereNotIn('character_id', $characterIdsToIgnore)
            ->inRandomOrder()
            ->cursor()
            ->firstWhere(fn (RefreshToken $refreshToken) => $refreshToken->hasScope('esi-universe.read_structures.v1'));
    }
}
