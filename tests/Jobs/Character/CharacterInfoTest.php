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

test('it stores the corporation title, not the cosmetic title id', function () {
    Bus::fake();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) [
        'name' => 'Some Character',
        'birthday' => '2015-03-24T11:37:00Z',
        'gender' => 'male',
        'race_id' => 1,
        'bloodline_id' => 3,
        'corporation_id' => 98000001,
        'achievement_score' => 1234,
        'corporation_title' => 'Chief Executive Officer',
        'character_title_id' => '0199a1b2-c3d4-7890-abcd-ef0123456789',
    ]));

    $job = new CharacterInfoJob(90000001);
    $job->executeJob($esi);

    expect(CharacterInfo::find(90000001))
        ->title->toBe('Chief Executive Officer');
});
