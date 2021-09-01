<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    $this->job_container = new JobContainer([
        'alliance_id' => $this->test_character->character_id,
    ]);
});

test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();


    AllianceInfoJob::dispatch($this->job_container)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', AllianceInfoJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildAllianceInfoMockEsiData();

    $job = new AllianceInfoJob($this->job_container);

    // Run InfoAction
    //(new AllianceInfoAction)->execute(2113468987);
    $job->handle();


    //Assert that alliance_info is created
    $this->assertDatabaseHas('alliance_infos', [
        'name' => $mock_data->name,
    ]);
});

// Helpers
function buildAllianceInfoMockEsiData()
{
    $mock_data = AllianceInfo::factory()->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
