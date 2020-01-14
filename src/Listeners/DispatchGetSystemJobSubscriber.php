<?php


namespace Seatplus\Eveapi\Listeners;

use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Models\Universe\System;

class DispatchGetSystemJobSubscriber
{
    private $system_id;

    public function handleUniverseStationCreated($event)
    {
        $this->system_id = $event->station->system_id;

        $this->handleSystemId();
    }

    public function handleUniverseStructureCreated($event)
    {
        $this->system_id = $event->structure->solar_system_id;

        $this->handleSystemId();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            UniverseStationCreated::class,
            get_class($this) . '@handleUniverseStationCreated'
        );

        $events->listen(
            UniverseStructureCreated::class,
            get_class($this) . '@handleUniverseStructureCreated'
        );
    }

    private function handleSystemId()
    {
        if(System::find($this->system_id))
            return;

        $job = new ResolveUniverseSystemBySystemIdJob;
        $job->setSystemId($this->system_id);

        dispatch($job)->onQueue('default');
    }

}
