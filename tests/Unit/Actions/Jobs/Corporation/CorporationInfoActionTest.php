<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Corporation;

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Actions\Jobs\Corporation\CorporationInfoAction;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationInfoActionTest extends TestCase
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
        (new CorporationInfoAction)->execute($mock_data->corporation_id);


        $mock_data->alliance_id
            ? Bus::assertDispatched(AllianceInfo::class)
            : Bus::assertNotDispatched(AllianceInfo::class);

        //Assert that test character is now created
        $this->assertDatabaseHas('corporation_infos', [
            'name' => $mock_data->name
        ]);
    }

    private function buildMockEsiData()
    {

        $mock_data = factory(CorporationInfo::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
