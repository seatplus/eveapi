<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);
uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

it('runs skill job', function () {
    expect(Skill::all())->toHaveCount(0);

    buildSkillMockEsiData();

    expect($this->test_character->total_sp)->toBeNull();

    (new SkillsJob($this->job_container))->handle();

    expect(Skill::all())->toHaveCount(5);
    expect(Skill::first()->type)->toBeInstanceOf(Type::class);

    $this->assertNotNull($this->test_character->refresh()->total_sp);

    expect($this->test_character->refresh()->skills)->toHaveCount(5);
});

it('observes skill creation', function () {
    Queue::assertNothingPushed();

    Skill::factory(['skill_id' => 123])->create();

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
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
