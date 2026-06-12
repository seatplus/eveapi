<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('runs skill job', function () {
    expect(Skill::all())->toHaveCount(0);

    $mockedSkills = Event::fakeFor(
        fn () => Skill::factory(['character_id' => testCharacter()->character_id])
            ->count(5)
            ->make()
    );

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) [
        'skills' => array_map(fn ($s) => (object) $s, $mockedSkills->toArray()),
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ]));

    expect($this->test_character->total_sp)->toBeNull();

    $job = new SkillsJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Skill::all())->toHaveCount(5);
    expect(Skill::first()->type)->toBeInstanceOf(Type::class);

    $this->assertNotNull($this->test_character->refresh()->total_sp);

    expect($this->test_character->refresh()->skills)->toHaveCount(5);
});

it('Dispatch Type job if skill is missing', function () {
    Queue::assertNothingPushed();

    $skill = Skill::factory(['skill_id' => 123])->make();

    expect($skill->type)->toBeNull();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) [
        'skills' => [(object) $skill->toArray()],
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ]));

    $job = new SkillsJob(testCharacter()->character_id);
    $job->executeJob($esi);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

it('does not update skills and character info if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new SkillsJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Skill::all())->toHaveCount(0);
});
