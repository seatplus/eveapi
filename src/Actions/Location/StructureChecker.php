<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Structure;

class StructureChecker extends LocationChecker
{
    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    private $refresh_token;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\ResolveUniverseStructureByIdAction
     */
    private $action;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
        $this->action = new ResolveUniverseStructureByIdAction($refresh_token);
    }

    public function check(Location $location)
    {
        if (
            // if locatable exists and if locatable is of type Station and if last update is greater then a week
            ($location->exists && is_a($location->locatable, Structure::class) && $location->locatable->updated_at < carbon()->subWeek())
            // or if location does not exist and id is not between 60000000 and 64000000
            || (!$location->exists && !($location->location_id > 60000000 && $location->location_id < 64000000))
        )
            $this->action->execute($location->location_id);


        $this->next($location);
    }
}
