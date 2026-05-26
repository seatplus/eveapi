<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
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

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mock_data->toArray()));

    $job = new CharacterInfoJob($mock_data['character_id']);
    $job->executeJob($esi);

    expect(CharacterInfo::where('name', $mock_data['name'])->exists())->toBeTrue();
});
