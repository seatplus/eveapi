<?php


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Skills\SkillQueue;
use Seatplus\Eveapi\Models\Universe\Type;


beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();
});

it('runs skill job', function () {
    expect(SkillQueue::all())->toHaveCount(0);

    buildSkillQueueMockEsiData();

    expect($this->test_character->total_sp)->toBeNull();

    (new SkillQueueJob(testCharacter()->character_id))->handle();

    expect(SkillQueue::all())->toHaveCount(5)
        ->and(SkillQueue::first()->type)->toBeInstanceOf(Type::class)
        ->and($this->test_character->refresh()->skill_queues)->toHaveCount(5);

});

it('dispatch type job if skill_id is not yet in the type table', function () {

    expect(SkillQueue::all())->toHaveCount(0);

    Queue::assertNothingPushed();

    //SkillQueue::factory(['skill_id' => 123])->make();

    $mock_data = buildSkillQueueMockEsiData();

    // Delete all types
    Type::query()->delete();

    (new SkillQueueJob(testCharacter()->character_id))->handle();

    expect(SkillQueue::first())->type->not()->toBeInstanceOf(Type::class);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});

it('deletes old queue items', function () {
    // create old Dataa
    $old_data = Event::fakeFor(fn () => SkillQueue::factory(['character_id' => testCharacter()->character_id])->create());

    expect(SkillQueue::all())->toHaveCount(1);

    buildSkillQueueMockEsiData();

    expect($this->test_character->total_sp)->toBeNull();

    (new SkillQueueJob(testCharacter()->character_id))->handle();

    expect(SkillQueue::all())->toHaveCount(5);
    $this->assertNotCount(6, SkillQueue::all());
});

// Helpers
function buildSkillQueueMockEsiData()
{
    Queue::assertNothingPushed();

    $mock_data = SkillQueue::factory(['character_id' => testCharacter()->character_id])
        ->count(5)
        ->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
