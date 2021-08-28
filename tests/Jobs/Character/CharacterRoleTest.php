<?php

namespace Seatplus\Eveapi\Tests\Jobs\Character;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterRoleTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        CharacterRoleJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterRoleJob::class);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function retrieveTest()
    {
        $mock_data = $this->buildMockEsiData();

        $refresh_token = RefreshToken::factory()->make([
            'character_id' => $mock_data->character_id,
            'scopes' => ['esi-characters.read_corporation_roles.v1'],
        ]);


        // Run CharacterRoleAction
        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        (new CharacterRoleJob($job_container))->handle();

        //Assert that test character is now created
        $this->assertDatabaseHas('character_roles', [
            'character_id' => $mock_data->character_id,
        ]);
    }

    private function buildMockEsiData()
    {
        $mock_data = CharacterRole::factory()->make([
            'roles' => ['Personnel_Manager'],
            'character_id' => $this->test_character->character_id,
        ]);

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }
}
