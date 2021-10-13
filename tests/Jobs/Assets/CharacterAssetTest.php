<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();

    $this->job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterAssetJob::dispatch($this->job_container)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterAssetJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildAssetMockEsiData();

    // Run job
    (new CharacterAssetJob($this->job_container))->handle();


    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }
});

/**
 * @runTestsInSeparateProcesses
 */
it('cleans up assets', function () {
    $old_data = Asset::factory()->count(5)->create([
        'assetable_id' => $this->test_character->character_id,
    ]);

    // assert that old data is present before CharacterAssetsCleanUpAction
    foreach ($old_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }


    $mock_data = buildAssetMockEsiData();

    // Run CharacterAssetsAction
    (new CharacterAssetJob($this->job_container))->handle();


    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }

    // assert if old data has been removed thanks to CharacterAssetsCleanupAction
    foreach ($old_data as $data) {
        $this->assertCount(
            0,
            Asset::where('assetable_id', $this->test_character->character_id)
            ->where('item_id', $data->item_id)
            ->get()
        );
    }
});

// Helpers
function buildAssetMockEsiData()
{
    $mock_data = Asset::factory()->count(5)->make([
        'assetable_id' => testCharacter()->character_id,
    ]);

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
