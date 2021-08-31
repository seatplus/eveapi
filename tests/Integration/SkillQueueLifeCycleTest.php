<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
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
    $this->assertCount(0, SkillQueue::all());

    buildMockEsiData();

    $this->assertNull($this->test_character->total_sp);

    (new SkillQueueJob($this->job_container))->handle();

    $this->assertCount(5, SkillQueue::all());
    $this->assertInstanceOf(Type::class, SkillQueue::first()->type);

    $this->assertCount(5, $this->test_character->refresh()->skill_queues);
});

it('observes skill creation', function () {
    Queue::assertNothingPushed();

    SkillQueue::factory(['skill_id' => 123])->create();

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

it('deletes old queue items', function () {
    // create old Dataa
    $old_data = Event::fakeFor(fn () => SkillQueue::factory(['character_id' => $this->test_character->character_id])->create());

    $this->assertCount(1, SkillQueue::all());

    buildMockEsiData();

    $this->assertNull($this->test_character->total_sp);

    (new SkillQueueJob($this->job_container))->handle();

    $this->assertCount(5, SkillQueue::all());
    $this->assertNotCount(6, SkillQueue::all());
});

// Helpers
function buildMockEsiData()
{
    Queue::assertNothingPushed();

    $mock_data = Event::fakeFor(
        fn () => SkillQueue::factory(['character_id' => $this->test_character->character_id])
        ->count(5)
        ->make()
    );

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
