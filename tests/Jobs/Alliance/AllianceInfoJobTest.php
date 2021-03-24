<?php

namespace Seatplus\Eveapi\Tests\Jobs\Alliance;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Tests\TestCase;

class AllianceInfoJobTest extends TestCase
{
    /** @test */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $job_container = new JobContainer([
            'alliance_id' => $this->test_character->character_id
        ]);

        AllianceInfoJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', AllianceInfoJob::class);
    }
}