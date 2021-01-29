<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Assets;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Actions\Jobs\Assets\GetCharacterAssetsNamesAction;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class GetCharacterAssetsNamesActionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /**
     * @var \Seatplus\Eveapi\Actions\Jobs\Assets\GetCharacterAssetsNamesAction
     */
    private $action;

    /**
     * @var string
     */
    private $name_to_create;

    public function setUp(): void
    {

        parent::setUp();

        $this->action = new GetCharacterAssetsNamesAction;
        $this->name_to_create = 'TestName';
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_updates_a_name()
    {
        $type = Type::factory()->create();

        $group = Event::fakeFor(fn () => factory(Group::class)->create([
            'group_id' => $type->group_id,
            'category_id' => 22
        ]));

        $category = factory(Category::class)->create([
            'category_id' => $group->category_id
        ]);

        $asset = Asset::factory()->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->make(['character_id' =>$asset->assetable_id]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

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
        $type = Type::factory()->create();

        $group = Event::fakeFor(fn () => factory(Group::class)->create([
            'group_id' => $type->group_id,
            'category_id' => 11
        ]));

        factory(Category::class)->create([
            'category_id' => $group->category_id
        ]);

        $asset = Asset::factory()->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->make(['character_id' =>$asset->assetable_id]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

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

        $group = Event::fakeFor(fn () => factory(Group::class)->create([
            'group_id' => $type->group_id,
            'category_id' => 5 //Only Celestials, Ships, Deployable, Starbases, Orbitals and Structures might be named
        ]));

        $asset = Asset::factory()->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->make(['character_id' =>$asset->assetable_id]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

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
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        /*$refresh_token = factory(RefreshToken::class)->create([
            'character_id' => $asset->character_id
        ]);*/
        $refresh_token = factory(RefreshToken::class)->make(['character_id' =>$asset->assetable_id]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

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

        $group = Event::fakeFor(fn () => factory(Group::class)->create([
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

        $refresh_token = factory(RefreshToken::class)->make(['character_id' =>$asset->assetable_id]);

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
        /*$this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);*/

    }


}
