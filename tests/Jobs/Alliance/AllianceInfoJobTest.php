<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    AllianceInfoJob::dispatch($this->test_character->character_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', AllianceInfoJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildAllianceInfoMockEsiData();

    $job = new AllianceInfoJob($this->test_character->character_id);

    // Run InfoAction
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
