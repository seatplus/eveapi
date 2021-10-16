<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Corporation\CorporationMemberTrackingAction;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    CorporationMemberTrackingJob::dispatch($job_container)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CorporationMemberTrackingJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildCorporationMemberMockEsiData();

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
});

// Helpers
function buildCorporationMemberMockEsiData()
{
    $mock_data = CorporationMemberTracking::factory()->make([
        'character_id' => testCharacter()->character_id,
        'corporation_id' => testCharacter()->corporation->corporation_id,
    ]);

    mockRetrieveEsiDataAction([$mock_data->toArray()]);

    return $mock_data;
}
