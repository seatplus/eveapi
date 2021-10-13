<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameDispatchJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;


uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();

    $this->job_container = $job_container = new JobContainer([
        'refresh_token' => $this->test_character->refresh_token,
    ]);

    //$this->job = new CharacterAssetsNameJob($job_container);
    $this->name_to_create = 'TestName';
});

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterAssetsNameJob::dispatch($this->job_container, [1])->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterAssetsNameJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
it('updates a name', function () {
    $type = Event::fakeFor(fn () => Type::factory()->create([
        'group_id' => Group::factory()->create([
            'category_id' => Category::factory()->create([
                'category_id' => 22,
            ]),
        ]),
    ]));

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    //Assert that character asset created has no name
    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    mockRetrieveEsiDataAction([
        [
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create,
        ],
    ]);

    CharacterAssetsNameJob::dispatchNow($this->job_container, [$asset->item_id]);

    //Assert that character asset created has no name
    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);

    $this->assertNotNull(cache()->store('file')->get($asset->item_id));
});

/**
 * @runTestsInSeparateProcesses
 */
it('does not update for wrong category', function () {
    $type = Event::fakeFor(fn () => Type::factory()->create([
        'group_id' => Group::factory()->create([
            'category_id' => Category::factory()->create([
                'category_id' => 11,
            ]),
        ]),
    ]));

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    //Assert that character asset created has no name
    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    $this->assertRetrieveEsiDataIsNotCalled();

    Queue::fake();

    CharacterAssetsNameDispatchJob::dispatchNow($this->job_container);

    Queue::assertNotPushed(CharacterAssetsNameJob::class);

    //Assert that character asset created has no name
    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('does not run if category id is out of scope', function () {
    $type = Type::factory()->create();

    $group = Event::fakeFor(fn () => Group::factory()->create([
        'group_id' => $type->group_id,
        'category_id' => 5, //Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
    ]));

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    //Assert that character asset created has no name
    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    $this->assertRetrieveEsiDataIsNotCalled();

    Queue::fake();

    CharacterAssetsNameDispatchJob::dispatchNow($this->job_container);

    Queue::assertNotPushed(CharacterAssetsNameJob::class);

    //Assert that character asset created has no name
    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('does not run if group is missing', function () {
    $type = Type::factory()->create();

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    //Assert that character asset created has no name
    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    /*$refresh_token = RefreshToken::factory()->create([
        'character_id' => $asset->character_id
    ]);*/
    $refresh_token = RefreshToken::factory()->make(['character_id' => $asset->assetable_id]);

    $this->assertRetrieveEsiDataIsNotCalled();

    Queue::fake();

    CharacterAssetsNameDispatchJob::dispatchNow($this->job_container);

    Queue::assertNotPushed(CharacterAssetsNameJob::class);

    //Assert that character asset created has no name
    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

/**
 * @runTestsInSeparateProcesses
 */
it('runs the job', function () {
    $type = Event::fakeFor(fn () => Type::factory()->create());

    $group = Event::fakeFor(fn () => Group::factory()->create([
        'group_id' => $type->group_id,
        'category_id' => 22,
    ]));

    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]));


    //Assert that character asset created has no name
    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    $refresh_token = RefreshToken::factory()->make(['character_id' => $asset->assetable_id]);

    mockRetrieveEsiDataAction([
        [
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create,
        ],
    ]);

    $job_container = new JobContainer(['refresh_token' => $refresh_token]);

    CharacterAssetsNameJob::dispatchNow($job_container, [$asset->item_id]);

    //Assert that character asset created has name
    $this->assertCount(
        1,
        Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->where('name', $this->name_to_create)
        ->get()
    );
});
