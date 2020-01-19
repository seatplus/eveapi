<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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
