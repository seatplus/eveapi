<?php

namespace Seatplus\Eveapi\Tests\Jobs\Assets;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterAssetsNameJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    private CharacterAssetsNameJob $job;

    private string $name_to_create;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $job_container = $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token
        ]);

        $this->job = new CharacterAssetsNameJob($job_container);
        $this->name_to_create = 'TestName';
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function testIfJobIsQueued()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        dispatch($this->job)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterAssetsNameJob::class);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_updates_a_name()
    {
        $type = Event::fakeFor(fn () => Type::factory()->create([
            'group_id' => Group::factory()->create([
                'category_id' => Category::factory()->create([
                    'category_id' => 22
                ])
            ])
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
            'name' => null
        ]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->job->handle();

        //Assert that character asset created has no name
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

        $this->assertNotNull(cache()->store('file')->get($asset->item_id));
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_update_for_wrong_category()
    {
        $type = Event::fakeFor(fn () => Type::factory()->create([
            'group_id' => Group::factory()->create([
                'category_id' => Category::factory()->create([
                    'category_id' => 11
                ])
            ])
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
            'name' => null
        ]);

        $this->assertRetrieveEsiDataIsNotCalled();

        $this->job->handle();

        //Assert that character asset created has no name
        $this->assertDatabaseMissing('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_run_if_category_id_is_out_of_scope()
    {
        $type = Type::factory()->create();

        $group = Event::fakeFor(fn () => Group::factory()->create([
            'group_id' => $type->group_id,
            'category_id' => 5 //Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
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
            'name' => null
        ]);

        $this->assertRetrieveEsiDataIsNotCalled();

        $this->job->handle();

        //Assert that character asset created has no name
        $this->assertDatabaseMissing('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_run_if_group_is_missing()
    {
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
            'name' => null
        ]);

        /*$refresh_token = RefreshToken::factory()->create([
            'character_id' => $asset->character_id
        ]);*/
        $refresh_token = RefreshToken::factory()->make(['character_id' =>$asset->assetable_id]);

        $this->assertRetrieveEsiDataIsNotCalled();

        $this->job->handle();

        //Assert that character asset created has no name
        $this->assertDatabaseMissing('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_runs_the_job()
    {
        $type = Event::fakeFor( fn() => Type::factory()->create());

        $group = Event::fakeFor(fn () => Group::factory()->create([
            'group_id' => $type->group_id,
            'category_id' => 22
        ]));

        $asset = Event::fakeFor( fn() => Asset::factory()->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]));


        //Assert that character asset created has no name
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = RefreshToken::factory()->make(['character_id' =>$asset->assetable_id]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $job_container = new JobContainer(['refresh_token' => $refresh_token]);

        $job = new CharacterAssetsNameJob($job_container);
        $job->handle();


        //Assert that character asset created has name
        $this->assertCount(1, Asset::where('assetable_id', $asset->assetable_id)
            ->where('item_id',$asset->item_id)
            ->where('name', $this->name_to_create)
            ->get()
        );

    }

}
