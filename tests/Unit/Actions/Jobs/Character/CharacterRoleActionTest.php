<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Character;

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterInfoAction;
use Seatplus\Eveapi\Actions\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterRoleActionTest extends TestCase
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
        (new CharacterRoleAction())->execute($this->test_character->refresh_token);


    }

    private function buildMockEsiData()
    {

        $mock_data = factory(CharacterRole::class)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
