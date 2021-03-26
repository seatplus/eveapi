<?php

namespace Seatplus\Eveapi\Tests\Jobs\Character;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterInfoTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /** @test */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        CharacterInfoJob::dispatch()->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterInfoJob::class);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {

        $mock_data = $this->buildMockEsiData();

        // Stop CharacterInfoAction dispatching a new job
        Bus::fake();

        // Run InfoAction
        $job_container = new JobContainer([
            'character_id' => $mock_data['character_id']
        ]);

        $job = new CharacterInfoJob($job_container);

        $job->handle();

        //(new CharacterInfoAction)->execute($mock_data['character_id']);

        //Assert that test character is now created
        $this->assertDatabaseHas('character_infos', [
            'name' => $mock_data['name']
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = CharacterInfo::factory()->make();

       /* $faker = Factory::create();
        $alliance_id = $faker->optional()->numberBetween(99000000,100000000);

        $mock_data = $mock_data->toArray();
        $mock_data['alliance_id'] = $alliance_id;*/

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
