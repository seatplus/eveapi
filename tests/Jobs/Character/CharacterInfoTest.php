<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

it('dispatches job on default queue by character_id', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterInfoJob::dispatch(character_id: 123)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterInfoJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildCharacterInfoMockEsiData();

    // Stop CharacterInfoAction dispatching a new job
    Bus::fake();

    // Run InfoAction

    $job = new CharacterInfoJob($mock_data['character_id']);

    $job->handle();

    //(new CharacterInfoAction)->execute($mock_data['character_id']);

    //Assert that test character is now created
    $this->assertDatabaseHas('character_infos', [
        'name' => $mock_data['name'],
    ]);
});

// Helpers
function buildCharacterInfoMockEsiData()
{
    $mock_data = CharacterInfo::factory()->make();

    /* $faker = Factory::create();
     $alliance_id = $faker->optional()->numberBetween(99000000,100000000);

     $mock_data = $mock_data->toArray();
     $mock_data['alliance_id'] = $alliance_id;*/

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
