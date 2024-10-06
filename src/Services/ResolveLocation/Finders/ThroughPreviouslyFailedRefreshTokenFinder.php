<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Finders;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\LocationRefreshToken;
use Seatplus\Eveapi\Models\RefreshToken;

class ThroughPreviouslyFailedRefreshTokenFinder implements FinderInterface
{
    #[\Override]
    public function handle(int $location_id, Collection $tracings): ?RefreshToken
    {

        $record = $tracings
            ->sortBy('updated_at')
            ->first(fn (LocationRefreshToken $tracking) => $tracking->attempts < 5);

        // check if record is LocationRefreshToken
        if ($record instanceof LocationRefreshToken) {
            return $record->refresh_token;
        }

        return null;
    }
}
