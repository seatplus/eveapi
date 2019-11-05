<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Character;

use Illuminate\Support\Facades\Bus;
use Mockery;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterInfoAction;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;

class InfoActionTest extends TestCase
{

    //TODO extend more unit test for actions

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {
        $this->mockRetrieveEsiDataAction();

        // First remove the test characters entry in character_infos
        CharacterInfo::find($this->test_character->character_id)->delete();

        // Stop CharacterInfoAction dispatching a new job
        Bus::fake();

        $this->assertDatabaseMissing('character_infos', [
            'name' => $this->test_character->name
        ]);

        // Run InfoAction
        (new CharacterInfoAction)->execute(2113468987);

        // Assert that Alliance Info has dispatched
        Bus::assertDispatched(AllianceInfo::class);

        //Assert that test character is now created
        $this->assertDatabaseHas('character_infos', [
            'name' => $this->test_character->name
        ]);

    }

    private function mockRetrieveEsiDataAction()
    {
        $body = json_encode([
            "alliance_id" => 99006828,
            "ancestry_id"=> 11,
            "birthday"=> "2017-11-24T14:27:47Z",
            "bloodline_id"=> 1,
            "corporation_id"=> 98540583,
            "description"=> "\u53ea\u6253\u67b6\uff0c\u4e0d\u5237\u602ao(\u2229_\u2229)o ",
            "gender"=> "male",
            "name"=> $this->test_character->name,
            "race_id"=> 1,
            "security_status"=> 3.7851618081017286,
            "title"=> "\u5317\u6597\u4e03\u661f"
        ]);

        $response = new EsiResponse($body, [], 'now', 200);

        $mock = Mockery::mock('overload:Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction');
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn($response);
    }

}
