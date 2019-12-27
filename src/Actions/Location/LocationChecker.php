<?php


namespace Seatplus\Eveapi\Actions\Location;


use Seatplus\Eveapi\Models\Universe\Location;

abstract class LocationChecker
{

    protected $successor;

    public abstract function check(Location $location);

    public function succeedWith(LocationChecker $successor)
    {
        $this->successor = $successor;
    }

    public function next(Location $location)
    {
        if ($this->successor)
            $this->successor->check($location);
    }

}
