<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Universe\Type;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

it('runs skill job', function () {
    expect(Skill::all())->toHaveCount(0);

    buildSkillMockEsiData();

    expect($this->test_character->total_sp)->toBeNull();

    (new SkillsJob(testCharacter()->character_id))->handle();

    expect(Skill::all())->toHaveCount(5);
    expect(Skill::first()->type)->toBeInstanceOf(Type::class);

    $this->assertNotNull($this->test_character->refresh()->total_sp);

    expect($this->test_character->refresh()->skills)->toHaveCount(5);
});

it('Dispatch Type job if skill is missing', function () {
    Queue::assertNothingPushed();

    $skill = Skill::factory(['skill_id' => 123])->make();

    expect($skill->type)->toBeNull();

    mockRetrieveEsiDataAction([
        'skills' => [$skill->toArray()],
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ]);

    (new SkillsJob(testCharacter()->character_id))->handle();

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

it('does not update skills and character info if response is cached', function () {
    $response = \Mockery::mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = \Mockery::mock(\Seatplus\Eveapi\Jobs\Skills\SkillsJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(Skill::all())->toHaveCount(0);
});

// Helpers
function buildSkillMockEsiData()
{
    Queue::assertNothingPushed();
    $mocked_skills = Event::fakeFor(
        fn () => Skill::factory(['character_id' => testCharacter()->character_id])
            ->count(5)
            ->make()
    );
    Queue::assertNothingPushed();

    $mock_data = [
        'skills' => $mocked_skills->toArray(),
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ];

    mockRetrieveEsiDataAction($mock_data);

    return $mock_data;
}
