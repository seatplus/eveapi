<?php

namespace Seatplus\Eveapi\Events;

use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\Universe\Station;

class UniverseStationCreated
{
    use SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\Universe\Station
     */
    public $station;

    public function __construct(Station $station)
    {

        $this->station = $station;
    }
}
