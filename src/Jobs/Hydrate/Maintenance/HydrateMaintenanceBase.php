<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Maintenance;


use Illuminate\Bus\Batchable;
use Seatplus\Eveapi\Jobs\Hydrate\Hydrate;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class HydrateMaintenanceBase implements Hydrate
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

}