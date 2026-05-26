<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();

    $this->name_to_create = 'TestName';
});

test('if job is queued', function () {
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

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) [
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]]));

    $job = new CharacterAssetsNameJob($this->test_character->character_id);
    $job->executeJob($esi);

    expect(Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->where('name', $this->name_to_create)
        ->exists())->toBeTrue();
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

    expect(Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->whereNotNull('name')
        ->exists())->toBeFalse();
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

    expect(Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->whereNotNull('name')
        ->exists())->toBeFalse();
});

it('does not run if group is missing', function () {
    $type = Type::factory()->create();

    $asset = Asset::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'type_id' => $type->type_id,
        'is_singleton' => true,
    ]);

    expect(Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->whereNotNull('name')
        ->exists())->toBeFalse();
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

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([(object) [
        'item_id' => $asset->item_id,
        'name' => $this->name_to_create,
    ]]));

    $job = new CharacterAssetsNameJob($asset->assetable_id);
    $job->executeJob($esi);

    expect(Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->where('name', $this->name_to_create)
        ->count())->toBe(1);
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

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterAssetsNameJob($this->test_character->character_id);
    $job->executeJob($esi);

    expect(Asset::where('assetable_id', $asset->assetable_id)
        ->where('item_id', $asset->item_id)
        ->whereNull('name')
        ->exists())->toBeTrue();
});
