<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\LocationRefreshTokens;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughPreviouslyFailedRefreshTokenFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {

        return $tracings
            ->sortBy('updated_at')
            ->first(fn (LocationRefreshTokens $tracking) => $tracking->attempts <5)?->refresh_token;
    }
}
