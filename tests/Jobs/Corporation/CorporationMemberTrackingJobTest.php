<?php

namespace Seatplus\Eveapi\Tests\Jobs\Corporation;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationMemberTrackingJobTest extends TestCase
{
    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

       $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token
        ]);

        CorporationMemberTrackingJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CorporationMemberTrackingJob::class);
    }

    /** @test */
    public function hasManualDispatchableJobInterfaceImplemented()
    {
        $job = new CorporationMemberTrackingJob;

        $this->assertEquals('Director', $job->getRequiredEveCorporationRole());
        $this->assertEquals('esi-corporations.track_members.v1', $job->getRequiredScope());
        $this->assertEquals('corporation.member_tracking', $job->getRequiredPermission());
        $this->assertEquals('corporation.member_tracking', $job->getRequiredPermission());
    }

}
