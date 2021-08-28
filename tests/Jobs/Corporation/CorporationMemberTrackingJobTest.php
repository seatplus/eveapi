<?php

namespace Seatplus\Eveapi\Tests\Jobs\Corporation;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Corporation\CorporationMemberTrackingAction;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationMemberTrackingJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

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
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        CorporationMemberTrackingJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CorporationMemberTrackingJob::class);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {
        $mock_data = $this->buildMockEsiData();

        // Stop Action dispatching a new job
        Bus::fake();

        $this->assertDatabaseMissing('corporation_member_trackings', [
            'corporation_id' => $this->test_character->corporation->corporation_id,
        ]);

        // Run Action
        //(new CorporationMemberTrackingAction)->execute($this->test_character->refresh_token);
        $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
        (new CorporationMemberTrackingJob($job_container))->handle();

        //Assert that test character is now created
        $this->assertDatabaseHas('corporation_member_trackings', [
            'corporation_id' => $this->test_character->corporation->corporation_id,
        ]);
    }

    private function buildMockEsiData()
    {
        $mock_data = CorporationMemberTracking::factory()->make([
            'character_id' => $this->test_character->character_id,
            'corporation_id' => $this->test_character->corporation->corporation_id,
        ]);

        $this->mockRetrieveEsiDataAction([$mock_data->toArray()]);

        return $mock_data;
    }
}
