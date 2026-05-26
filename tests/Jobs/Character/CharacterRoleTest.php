<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterRoleJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterRoleJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterRoleJob::class);
});

test('retrieve test', function () {
    $mock_data = CharacterRole::factory()->make([
        'roles' => ['Personnel_Manager'],
        'character_id' => testCharacter()->character_id,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new CharacterRoleJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(CharacterRole::where('character_id', $mock_data->character_id)->exists())->toBeTrue();
});
