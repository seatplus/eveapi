<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;

beforeEach(function () {
    Queue::fake();
});

test('job is being dispatched', function () {
    Queue::assertNothingPushed();

    CharacterContractsJob::dispatch(testCharacter()->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterContractsJob::class);
});

it('runs with empty response', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([]));

    $job = new CharacterContractsJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Contract::count())->toBe(0);
});

it('creates contract job', function () {
    $mock_data = Contract::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($c) => (object) $c, $mock_data->toArray())));

    Event::fakeFor(function () use ($esi) {
        $job = new CharacterContractsJob(testCharacter()->character_id);
        $job->executeJob($esi);
    });

    expect(Contract::all())->toHaveCount(5);
    expect($this->test_character->refresh()->contracts)->toHaveCount(5);
});

it('creates contract job other way', function () {
    $mock_data = Contract::factory()->count(5)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($c) => (object) $c, $mock_data->toArray())));

    Event::fakeFor(function () use ($esi) {
        $job = new CharacterContractsJob(testCharacter()->character_id);
        $job->executeJob($esi);
    });

    expect(Contract::all())->toHaveCount(5);
});
