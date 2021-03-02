<?php


namespace Seatplus\Eveapi\Services\ResolveLocation;


use Closure;
use Seatplus\Eveapi\Actions\Location\ResolveUniverseStationByIdAction;
use Seatplus\Eveapi\Actions\Location\ResolveUniverseStructureByIdAction;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class ResolveStructurePipe
{
    public function __construct(
        public int $location_id,
        public RefreshToken $refreshToken
    )
    {
    }

    public function handle(ResolveLocationDTO $payload, Closure $next)
    {
        // if station just return early
        if(is_a($payload->location->locatable, Station::class))
            return $next($payload);

        // if location is structure and last update is greater then a week don't bother no longer
        if(is_a($payload->location->locatable, Structure::class) && $payload->location->locatable->updated_at > carbon()->subWeek())
            return $next($payload);

        // if location_id is a potential structure_id ( >= 100000000)
        if($this->location_id >= 100_000_000) {
            $this->getStructure();
            $payload->log_message = sprintf('successfully resolved structure with id %s using refresh_token of %s',
                $this->location_id, $this->refreshToken->character->name
            );
        }

        return $next($payload);
    }

    private function getStructure()
    {
        $action = new ResolveUniverseStructureByIdAction($this->refreshToken);

        throw_unless(in_array($action->getRequiredScope(), $this->refreshToken->scopes), 'Trying to resolve a structure with a refresh token that is lacking the necessairy code');

        $action->execute($this->location_id);
    }

}
