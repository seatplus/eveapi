<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Alliance;

use Illuminate\Support\Facades\Bus;
use Mockery;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Actions\Jobs\Alliance\AllianceInfoAction;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterInfoAction;

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;
use Faker\Generator as Faker;
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

        $mock_data = factory(AllianceInfo::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
