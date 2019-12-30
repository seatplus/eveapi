<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Assets;


use Seatplus\Eveapi\Actions\Jobs\Assets\GetCharacterAssetsNamesAction;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
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
        $type = factory(Type::class)->create();

        $group = factory(Group::class)->create([
            'group_id' => $type->group_id,
            'category_id' => 22
        ]);

        $category = factory(Category::class)->create([
            'category_id' => $group->category_id
        ]);

        $asset = factory(CharacterAsset::class)->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->create([
            'character_id' => $asset->character_id
        ]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_update_for_wrong_category()
    {
        $type = factory(Type::class)->create();

        $group = factory(Group::class)->create([
            'group_id' => $type->group_id,
            'category_id' => 11
        ]);

        factory(Category::class)->create([
            'category_id' => $group->category_id
        ]);

        $asset = factory(CharacterAsset::class)->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->create([
            'character_id' => $asset->character_id
        ]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

        //Assert that character asset created has no name
        $this->assertDatabaseMissing('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_run_if_category_is_missing()
    {
        $type = factory(Type::class)->create();

        $group = factory(Group::class)->create([
            'group_id' => $type->group_id,
            'category_id' => 22
        ]);

        $asset = factory(CharacterAsset::class)->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->create([
            'character_id' => $asset->character_id
        ]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

        //Assert that character asset created has no name
        $this->assertDatabaseMissing('character_assets', [
            'character_id' => $asset->character_id,
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
        $type = factory(Type::class)->create();

        $asset = factory(CharacterAsset::class)->create([
            'type_id' => $type->type_id,
            'is_singleton' => true,
        ]);

        //Assert that character asset created has no name
        $this->assertDatabaseHas('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => null
        ]);

        $refresh_token = factory(RefreshToken::class)->create([
            'character_id' => $asset->character_id
        ]);

        $this->mockRetrieveEsiDataAction([
            [
                'item_id' => $asset->item_id,
                'name' => $this->name_to_create
            ]
        ]);

        $this->action->execute($refresh_token);

        //Assert that character asset created has no name
        $this->assertDatabaseMissing('character_assets', [
            'character_id' => $asset->character_id,
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create
        ]);

    }


}
