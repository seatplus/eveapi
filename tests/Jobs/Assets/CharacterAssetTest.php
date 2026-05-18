<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-assets.read_assets.v1']);
    $refresh_token->save();
});

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    CharacterAssetJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterAssetJob::class);
});

test('retrieve test', function () {
    $mock_data = buildAssetMockEsiData();

    runJob(new CharacterAssetJob($this->test_character->character_id));

    foreach ($mock_data as $data) {
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }
});

it('cleans up assets', function () {
    $old_data = Asset::factory()->count(5)->create([
        'assetable_id' => $this->test_character->character_id,
    ]);

    foreach ($old_data as $data) {
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }

    $mock_data = buildAssetMockEsiData();

    runJob(new CharacterAssetJob($this->test_character->character_id));

    foreach ($mock_data as $data) {
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }

    foreach ($old_data as $data) {
        $this->assertCount(
            0,
            Asset::where('assetable_id', $this->test_character->character_id)
                ->where('item_id', $data->item_id)
                ->get()
        );
    }
});

it('dispatches unknown location job', function () {
    buildAssetMockEsiData();

    runJob(new CharacterAssetJob($this->test_character->character_id));

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});

it('dispatches unknown types job', function () {
    buildAssetMockEsiData();

    runJob(new CharacterAssetJob($this->test_character->character_id));

    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch ResolveUniverseTypeByIdJob if type is known', function () {
    $type = Type::factory()->create();

    $assets = Asset::factory()->count(5)->create([
        'assetable_id' => testCharacter()->character_id,
        'type_id' => $type->type_id,
    ]);

    mockEsiClient(
        'assets->getCharactersCharacterIdAssets',
        makeEsiResult(array_map(fn ($a) => (object) $a, $assets->toArray()))
    );

    runJob(new CharacterAssetJob($this->test_character->character_id));

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch ResolveLocationJob if location is known', function () {
    $location = Location::factory()->create();

    $assets = Asset::factory()->count(5)->create([
        'assetable_id' => testCharacter()->character_id,
        'location_id' => $location->location_id,
    ]);

    mockEsiClient(
        'assets->getCharactersCharacterIdAssets',
        makeEsiResult(array_map(fn ($a) => (object) $a, $assets->toArray()))
    );

    runJob(new CharacterAssetJob($this->test_character->character_id));

    Queue::assertNotPushed(ResolveLocationJob::class);
});

// Helpers
function buildAssetMockEsiData()
{
    $mock_data = Asset::factory()->count(5)->make([
        'assetable_id' => testCharacter()->character_id,
    ]);

    mockEsiClient(
        'assets->getCharactersCharacterIdAssets',
        makeEsiResult(array_map(fn ($a) => (object) $a, $mock_data->toArray()))
    );

    return $mock_data;
}
