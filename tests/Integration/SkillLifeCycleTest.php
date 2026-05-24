<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CharactersSkills;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

it('runs skill job', function () {
    expect(Skill::all())->toHaveCount(0);

    $mocked_skills = Event::fakeFor(
        fn () => Skill::factory(['character_id' => testCharacter()->character_id])
            ->count(5)
            ->make()
    );

    $skillsDto = CharactersSkills::from((object) [
        'skills' => array_map(fn ($s) => (object) $s, $mocked_skills->toArray()),
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ]);
    $skillsDto->isCachedLoad = false;

    mockEsiClient('skills->getCharactersCharacterIdSkills', $skillsDto);

    expect($this->test_character->total_sp)->toBeNull();

    runJob(new SkillsJob(testCharacter()->character_id));

    expect(Skill::all())->toHaveCount(5);
    expect(Skill::first()->type)->toBeInstanceOf(Type::class);

    $this->assertNotNull($this->test_character->refresh()->total_sp);

    expect($this->test_character->refresh()->skills)->toHaveCount(5);
});

it('Dispatch Type job if skill is missing', function () {
    Queue::assertNothingPushed();

    $skill = Skill::factory(['skill_id' => 123])->make();

    expect($skill->type)->toBeNull();

    $skillsDto = CharactersSkills::from((object) [
        'skills' => [(object) $skill->toArray()],
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ]);
    $skillsDto->isCachedLoad = false;

    mockEsiClient('skills->getCharactersCharacterIdSkills', $skillsDto);

    runJob(new SkillsJob(testCharacter()->character_id));

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

it('does not update skills and character info if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new SkillsJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(Skill::all())->toHaveCount(0);
});
