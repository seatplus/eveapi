<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Character;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterInfoAction;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterInfoActionTest extends TestCase
{

    use MockRetrieveEsiDataAction;

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
        (new CharacterInfoAction)->execute($mock_data['character_id']);


        $mock_data['alliance_id']
            ? Bus::assertDispatched(AllianceInfo::class)
            : Bus::assertNotDispatched(AllianceInfo::class);

        //Assert that test character is now created
        $this->assertDatabaseHas('character_infos', [
            'name' => $mock_data['name']
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = factory(CharacterInfo::class)->make();

        $faker = Factory::create();
        $alliance_id = $faker->optional()->numberBetween(99000000,100000000);

        $mock_data = $mock_data->toArray();
        $mock_data['alliance_id'] = $alliance_id;

        $this->mockRetrieveEsiDataAction($mock_data);

        return $mock_data;
    }


}
