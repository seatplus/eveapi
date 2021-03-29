<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Illuminate\Bus\Batchable;
use Seatplus\Eveapi\Jobs\Hydrate\Hydrate;

abstract class HydrateMaintenanceBase implements Hydrate
{
    use Batchable;

}