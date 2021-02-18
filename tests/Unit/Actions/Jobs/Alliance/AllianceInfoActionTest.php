<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Alliance;

use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class AllianceInfoActionTest extends TestCase
{

    use MockRetrieveEsiDataAction;


    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {

        $mock_data = $this->buildMockEsiData();

        // Run InfoAction
        (new AllianceInfoAction)->execute(2113468987);


        //Assert that alliance_info is created
        $this->assertDatabaseHas('alliance_infos', [
            'name' => $mock_data->name
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = AllianceInfo::factory()->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
