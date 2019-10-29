<?php

namespace Seatplus\Eveapi\Tests\Jobs\Alliance;

use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Tests\TestCase;

class AllianceInfoTest extends TestCase
{
    /** @test */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        AllianceInfo::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', AllianceInfo::class);
    }
}