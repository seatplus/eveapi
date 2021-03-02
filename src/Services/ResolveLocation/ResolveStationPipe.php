<?php


namespace Seatplus\Eveapi\Services\ResolveLocation;


use Closure;
use Seatplus\Eveapi\Actions\Location\ResolveUniverseStationByIdAction;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;

class ResolveStationPipe
{
    public function __construct(
        public int $location_id
    )
    {
    }

    public function handle(ResolveLocationDTO $payload, Closure $next)
    {

        // if structure just return early
        if(is_a($payload->location->locatable, Structure::class))
            return $next($payload);

        // if location is station and last update is greater then a week don't bother no longer
        if(is_a($payload->location->locatable, Station::class) && $payload->location->locatable->updated_at > carbon()->subWeek())
            return $next($payload);

        if($this->location_id > 60_000_000 && $this->location_id < 64_000_000) {
            $this->getStation();
            $payload->log_message = sprintf('successfully resolved station with id %s', $this->location_id);
        }

        return $next($payload);
    }

    private function getStation()
    {
        (new ResolveUniverseStationByIdAction)->execute($this->location_id);
    }

}
