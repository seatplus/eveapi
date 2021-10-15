<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Esi\Jobs\Character\CharacterRoleAction;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    CharacterRoleJob::dispatch($job_container)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterRoleJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildCharacterRoleMockEsiData();

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
});

// Helpers
function buildCharacterRoleMockEsiData()
{
    $mock_data = CharacterRole::factory()->make([
        'roles' => ['Personnel_Manager'],
        'character_id' => testCharacter()->character_id,
    ]);

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
