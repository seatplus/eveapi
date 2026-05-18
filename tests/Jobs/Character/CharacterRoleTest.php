<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-characters.read_corporation_roles.v1']);
    $refresh_token->save();
});

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterRoleJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterRoleJob::class);
});

test('retrieve test', function () {
    Queue::fake();

    $mock_data = CharacterRole::factory()->make([
        'roles' => ['Personnel_Manager'],
        'character_id' => testCharacter()->character_id,
    ]);

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('characters->getCharactersCharacterIdRoles', $dto);

    runJob(new CharacterRoleJob($this->test_character->character_id));

    $this->assertDatabaseHas('character_roles', [
        'character_id' => $mock_data->character_id,
    ]);
});
