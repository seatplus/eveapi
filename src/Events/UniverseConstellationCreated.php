<?php


namespace Seatplus\Eveapi\Events;


use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\System;

class UniverseConstellationCreated
{
    use SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\Universe\System
     */
    public $constellation;

    public function __construct(Constellation $constellation)
    {

        $this->constellation = $constellation;
    }

}
