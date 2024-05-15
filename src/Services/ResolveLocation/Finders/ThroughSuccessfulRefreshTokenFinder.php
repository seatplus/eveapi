<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\LocationRefreshTokens;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughSuccessfulRefreshTokenFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {
        return $tracings->first(fn (LocationRefreshTokens $tracking) => $tracking->resolved)?->refresh_token;

        // if we have a resolved tracking, we can return the refresh token
        // in order to work with phpstan we need to check if the tracking is of type LocationRefreshTokens
       /* return $tracking instanceof LocationRefreshTokens
            ? $tracking->refresh_token
            : null;*/
    }
}
