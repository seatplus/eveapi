<?php

namespace Seatplus\Eveapi\Events;

use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\Universe\Structure;

class UniverseStructureCreated
{
    use SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\Universe\Station
     */
    public $structure;

    public function __construct(Structure $structure)
    {

        $this->structure = $structure;
    }
}
