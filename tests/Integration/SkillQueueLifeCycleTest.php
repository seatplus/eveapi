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
    expect(SkillQueue::all())->toHaveCount(0);

    buildSkillQueueMockEsiData();

    expect($this->test_character->total_sp)->toBeNull();

    (new SkillQueueJob($this->job_container))->handle();

    expect(SkillQueue::all())->toHaveCount(5);
    expect(SkillQueue::first()->type)->toBeInstanceOf(Type::class);

    expect($this->test_character->refresh()->skill_queues)->toHaveCount(5);
});

it('observes skill creation', function () {
    Queue::assertNothingPushed();

    SkillQueue::factory(['skill_id' => 123])->create();

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

it('deletes old queue items', function () {
    // create old Dataa
    $old_data = Event::fakeFor(fn () => SkillQueue::factory(['character_id' => $this->test_character->character_id])->create());

    expect(SkillQueue::all())->toHaveCount(1);

    buildSkillQueueMockEsiData();

    expect($this->test_character->total_sp)->toBeNull();

    (new SkillQueueJob($this->job_container))->handle();

    expect(SkillQueue::all())->toHaveCount(5);
    $this->assertNotCount(6, SkillQueue::all());
});

// Helpers
function buildSkillQueueMockEsiData()
{
    Queue::assertNothingPushed();

    $mock_data = Event::fakeFor(
        fn () => SkillQueue::factory(['character_id' => testCharacter()->character_id])
        ->count(5)
        ->make()
    );

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
