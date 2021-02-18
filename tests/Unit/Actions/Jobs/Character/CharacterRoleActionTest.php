<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Character;

use Seatplus\Eveapi\Actions\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Actions\RetrieveFromEsiInterface;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;
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

        $refresh_token = RefreshToken::factory()->make([
            'character_id' => $mock_data->character_id,
            'scopes' => ['esi-characters.read_corporation_roles.v1']
        ]);


        // Run CharacterRoleAction
        (new CharacterRoleAction)->execute($refresh_token);

        //Assert that test character is now created
        $this->assertDatabaseHas('character_roles', [
            'character_id' => $mock_data->character_id,
        ]);


    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function getActionClassTest()
    {

        // We require to mock RetrieveEsiRespone as the action is build in the constructor
        $mock_data = $this->buildMockEsiData();

        $refresh_token = RefreshToken::factory()->make([
            'character_id' => $mock_data->character_id,
            'scopes' => ['esi-characters.read_corporation_roles.v1']
        ]);

        $action_class = (new CharacterRoleJob)->getActionClass();

        $this->assertInstanceOf(CharacterRoleAction::class, $action_class);
        $this->assertInstanceOf(RetrieveFromEsiInterface::class, $action_class);

        // Run CharacterRoleAction because somehow that is needed with all the mocking.
        ($action_class)->execute($refresh_token);

    }

    /** @test */
    public function getRequiredScope()
    {
        $this->assertEquals('esi-characters.read_corporation_roles.v1',(new CharacterRoleAction)->getRequiredScope() );
    }

    private function buildMockEsiData()
    {

        $mock_data = CharacterRole::factory()->make([
            'roles' => ['Personnel_Manager']
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }


}
