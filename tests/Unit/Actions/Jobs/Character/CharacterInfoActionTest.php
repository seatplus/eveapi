<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Character;

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
        (new CharacterInfoAction)->execute($mock_data->character_id);

        // Assert that Alliance Info has dispatched
        Bus::assertDispatched(AllianceInfo::class);

        //Assert that test character is now created
        $this->assertDatabaseHas('character_infos', [
            'name' => $mock_data->name
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = factory(CharacterInfo::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
