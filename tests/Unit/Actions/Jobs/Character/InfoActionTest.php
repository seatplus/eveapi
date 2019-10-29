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
        $jsonString = file_get_contents(__DIR__ . '/../../../../Stubs/CharacterInfo.json');
        $json = json_decode($jsonString, true);

        $json['character_id'] = $this->test_character->character_id;
        $json['name'] = $this->test_character->name;

        $body = json_encode($json);

        $response = new EsiResponse($body, [], 'now', 200);

        $mock = Mockery::mock('overload:Seatplus\Eveapi\Actions\Eseye\RetrieveEsiDataAction');
        $mock->shouldReceive('execute')
            ->once()
            ->andReturn($response);


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

}
