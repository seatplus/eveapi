<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('runs skill queue job', function () {
    expect(SkillQueue::all())->toHaveCount(0);

    $mocked_skill_queue = Event::fakeFor(
        fn () => SkillQueue::factory(['character_id' => testCharacter()->character_id])
            ->count(5)
            ->make()
    );

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($s) => (object) $s, $mocked_skill_queue->toArray())));

    $job = new SkillQueueJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(SkillQueue::all())->toHaveCount(5);
    expect(SkillQueue::first()->type)->toBeInstanceOf(Type::class);
});

it('does not update skill queue if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new SkillQueueJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(SkillQueue::all())->toHaveCount(0);
});
