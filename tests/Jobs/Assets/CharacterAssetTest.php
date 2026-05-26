<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

beforeEach(function () {
    Queue::fake();
});

test('if job is queued', function () {
    Queue::assertNothingPushed();

    CharacterAssetJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', CharacterAssetJob::class);
});

test('retrieve test', function () {
    $esi = Mockery::mock(EsiClient::class);
    $mock_data = buildAssetMockEsiData($esi);

    $job = new CharacterAssetJob($this->test_character->character_id);
    $job->executeJob($esi);

    foreach ($mock_data as $data) {
        expect(Asset::where('assetable_id', $this->test_character->character_id)
            ->where('item_id', $data->item_id)
            ->exists())->toBeTrue();
    }
});

it('cleans up assets', function () {
    $old_data = Asset::factory()->count(5)->create([
        'assetable_id' => $this->test_character->character_id,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    $mock_data = buildAssetMockEsiData($esi);

    $job = new CharacterAssetJob($this->test_character->character_id);
    $job->executeJob($esi);

    foreach ($mock_data as $data) {
        expect(Asset::where('assetable_id', $this->test_character->character_id)
            ->where('item_id', $data->item_id)
            ->exists())->toBeTrue();
    }

    foreach ($old_data as $data) {
        expect(Asset::where('assetable_id', $this->test_character->character_id)
            ->where('item_id', $data->item_id)
            ->count())->toBe(0);
    }
});

it('dispatches unknown location job', function () {
    $esi = Mockery::mock(EsiClient::class);
    buildAssetMockEsiData($esi);

    $job = new CharacterAssetJob($this->test_character->character_id);
    $job->executeJob($esi);

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});

it('dispatches unknown types job', function () {
    $esi = Mockery::mock(EsiClient::class);
    buildAssetMockEsiData($esi);

    $job = new CharacterAssetJob($this->test_character->character_id);
    $job->executeJob($esi);

    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch ResolveUniverseTypeByIdJob if type is known', function () {
    $type = Type::factory()->create();

    $assets = Asset::factory()->count(5)->create([
        'assetable_id' => testCharacter()->character_id,
        'type_id' => $type->type_id,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($a) => (object) $a, $assets->toArray())));

    $job = new CharacterAssetJob($this->test_character->character_id);
    $job->executeJob($esi);

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch ResolveLocationJob if location is known', function () {
    $location = Location::factory()->create();

    $assets = Asset::factory()->count(5)->create([
        'assetable_id' => testCharacter()->character_id,
        'location_id' => $location->location_id,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($a) => (object) $a, $assets->toArray())));

    $job = new CharacterAssetJob($this->test_character->character_id);
    $job->executeJob($esi);

    Queue::assertNotPushed(ResolveLocationJob::class);
});

// Helpers
function buildAssetMockEsiData(MockInterface $esi): Collection
{
    $mock_data = Asset::factory()->count(5)->make([
        'assetable_id' => testCharacter()->character_id,
    ]);

    mockEsiTransport($esi, makeEsiResult(array_map(fn ($a) => (object) $a, $mock_data->toArray())));

    return $mock_data;
}
