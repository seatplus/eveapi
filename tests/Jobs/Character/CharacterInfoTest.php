<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

it('dispatches job on default queue by character_id', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterInfoJob::dispatch(character_id: 123)->onQueue('default');

    Queue::assertPushedOn('default', CharacterInfoJob::class);
});

test('retrieve test', function () {
    $mock_data = CharacterInfo::factory()->make();

    Bus::fake();

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('characters->getCharactersCharacterId', $dto);

    runJob(new CharacterInfoJob($mock_data['character_id']));

    $this->assertDatabaseHas('character_infos', [
        'name' => $mock_data['name'],
    ]);
});
