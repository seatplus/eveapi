<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\LocationRefreshToken;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughSuccessfulRefreshTokenFinder implements FinderInterface
{
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {
        $record = $tracings->first(fn (LocationRefreshToken $tracking) => $tracking->resolved);

        // if we have a resolved tracking, we can return the refresh token
        // in order to work with phpstan we need to check if the tracking is of type LocationRefreshTokens
        if ($record instanceof LocationRefreshToken) {
            return $record->refresh_token;
        }

        return null;
    }
}
