<?php

namespace Seatplus\Eveapi\Tests\Jobs\Status;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Status\Esi;
use Seatplus\Eveapi\Tests\TestCase;

class EsiTest extends TestCase
{
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        Esi::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', Esi::class);
    }

}