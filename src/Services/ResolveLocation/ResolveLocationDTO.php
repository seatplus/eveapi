<?php


namespace Seatplus\Eveapi\Services\ResolveLocation;


use Seatplus\Eveapi\Models\Universe\Location;
use Spatie\DataTransferObject\DataTransferObject;

class ResolveLocationDTO extends DataTransferObject
{
    public Location $location;

    public string $log_message;

}
