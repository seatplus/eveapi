<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_corporation_roles.v1']);
    $refresh_token->save();
});

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterRoleJob::dispatch($this->test_character->character_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterRoleJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildCharacterRoleMockEsiData();

    (new CharacterRoleJob($this->test_character->character_id))->handle();

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
