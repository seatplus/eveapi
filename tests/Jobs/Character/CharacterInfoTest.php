<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

it('dispatches job on default queue by character_id', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterInfoJob::dispatch(characterId: 123)->onQueue('default');

    Queue::assertPushedOn('default', CharacterInfoJob::class);
});

test('retrieve test', function () {
    $mockData = CharacterInfo::factory()->make();

    Bus::fake();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->toArray()));

    $job = new CharacterInfoJob($mockData['character_id']);
    $job->executeJob($esi);

    expect(CharacterInfo::where('name', $mockData['name'])->exists())->toBeTrue();
});
