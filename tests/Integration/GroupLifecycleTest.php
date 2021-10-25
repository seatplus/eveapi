<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseCategoryByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

test('new group id dispatches category job if group is not present', function () {
    Queue::fake();

    $group = Group::factory()->create();

    Queue::assertPushedOn('high', ResolveUniverseCategoryByIdJob::class);
});

test('new group does not dispatches group job if category is present', function () {
    Queue::fake();

    $category = Category::factory()->create();

    $group = Group::factory()->create([
        'category_id' => $category->category_id,
    ]);

    Queue::assertNotPushed(ResolveUniverseCategoryByIdJob::class);
});

it('dispatches assets name job', function () {
    $type = Type::factory()->create();



    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    Queue::fake();

    $group = Group::factory()->create([
        'group_id' => $type->group_id,
        'category_id' => 6,
    ]);


    Queue::assertPushedOn('high', CharacterAssetsNameJob::class);
});
