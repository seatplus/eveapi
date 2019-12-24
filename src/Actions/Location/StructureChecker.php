<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;

class StructureChecker extends LocationChecker
{
    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    private $refresh_token;

    public function __construct(RefreshToken $refresh_token)
    {

        $this->refresh_token = $refresh_token;
    }

    public function check(Location $location)
    {
        if (is_null($location->locatable))
        {

        }
    }
}
