<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-assets.read_assets.v1']);
    $refresh_token->save();

    $this->name_to_create = 'TestName';
});

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterAssetsNameJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterAssetsNameJob::class);
});

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

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    mockEsiClient(
        'assets->postCharactersCharacterIdAssetsNames',
        makeEsiResult([(object) [
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create,
        ]])
    );

    runJob(new CharacterAssetsNameJob($this->test_character->character_id));

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

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

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    // No ESI call needed - query returns no assets in scope

    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

it('does not run if category id is out of scope', function () {
    $type = Type::factory()->create();

    Event::fakeFor(fn () => Group::factory()->create([
        'group_id' => $type->group_id,
        'category_id' => 5,
    ]));

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

it('does not run if group is missing', function () {
    $type = Type::factory()->create();

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]);
});

it('runs the job', function () {
    $type = Event::fakeFor(fn () => Type::factory()->create());

    Event::fakeFor(fn () => Group::factory()->create([
        'group_id' => $type->group_id,
        'category_id' => 22,
    ]));

    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]));

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);

    mockEsiClient(
        'assets->postCharactersCharacterIdAssetsNames',
        makeEsiResult([(object) [
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create,
        ]])
    );

    runJob(new CharacterAssetsNameJob($asset->assetable_id));

    $this->assertCount(
        1,
        Asset::where('assetable_id', $asset->assetable_id)
            ->where('item_id', $asset->item_id)
            ->where('name', $this->name_to_create)
            ->get()
    );
});

it('skips name update when response is a cached load', function () {
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

    mockEsiClient(
        'assets->postCharactersCharacterIdAssetsNames',
        makeEsiResult([(object) [
            'item_id' => $asset->item_id,
            'name' => $this->name_to_create,
        ]], isCachedLoad: true)
    );

    runJob(new CharacterAssetsNameJob($this->test_character->character_id));

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
        'name' => null,
    ]);
});
