<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\Skill;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

it('runs skill job', function () {
    $this->assertCount(0, Skill::all());

    buildMockEsiData();

    $this->assertNull($this->test_character->total_sp);

    (new SkillsJob($this->job_container))->handle();

    $this->assertCount(5, Skill::all());
    $this->assertInstanceOf(Type::class, Skill::first()->type);

    $this->assertNotNull($this->test_character->refresh()->total_sp);

    $this->assertCount(5, $this->test_character->refresh()->skills);
});

it('observes skill creation', function () {
    Queue::assertNothingPushed();

    Skill::factory(['skill_id' => 123])->create();

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

// Helpers
function buildMockEsiData()
{
    Queue::assertNothingPushed();
    $mocked_skills = Event::fakeFor(
        fn () => Skill::factory(['character_id' => $this->test_character->character_id])
        ->count(5)
        ->make()
    );
    Queue::assertNothingPushed();

    $mock_data = [
        'skills' => $mocked_skills->toArray(),
        'total_sp' => 1337,
        'unallocated_sp' => 42,
    ];

    $this->mockRetrieveEsiDataAction($mock_data);

    return $mock_data;
}
