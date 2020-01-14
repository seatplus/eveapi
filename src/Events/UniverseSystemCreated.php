<?php


namespace Seatplus\Eveapi\Events;


use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\Universe\System;

class UniverseSystemCreated
{
    use SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\Universe\System
     */
    public $system;

    public function __construct(System $system)
    {

        $this->system = $system;
    }

}
