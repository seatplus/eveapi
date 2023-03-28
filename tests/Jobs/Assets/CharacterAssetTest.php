<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-assets.read_assets.v1']);
    $refresh_token->save();
});

/**
 * @runTestsInSeparateProcesses
 */
test('if job is queued', function () {
    Queue::fake();

    // Assert that no jobs were pushed...
    Queue::assertNothingPushed();

    CharacterAssetJob::dispatch($this->test_character->character_id)->onQueue('default');

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('default', CharacterAssetJob::class);
});

/**
 * @runTestsInSeparateProcesses
 */
test('retrieve test', function () {
    $mock_data = buildAssetMockEsiData();

    // Run job
    (new CharacterAssetJob($this->test_character->character_id))->handle();

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }
});

/**
 * @runTestsInSeparateProcesses
 */
it('cleans up assets', function () {
    $old_data = Asset::factory()->count(5)->create([
        'assetable_id' => $this->test_character->character_id,
    ]);

    // assert that old data is present before CharacterAssetsCleanUpAction
    foreach ($old_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }


    $mock_data = buildAssetMockEsiData();

    // Run CharacterAssetsAction
    (new CharacterAssetJob($this->test_character->character_id))->handle();


    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }

    // assert if old data has been removed thanks to CharacterAssetsCleanupAction
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
    $mock_data = buildAssetMockEsiData();

    // Run CharacterAssetsAction
    (new CharacterAssetJob($this->test_character->character_id))->handle();

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('high', ResolveLocationJob::class);
});

it('dispatches unknown types job', function () {
    $mock_data = buildAssetMockEsiData();

    // Run CharacterAssetsAction
    (new CharacterAssetJob($this->test_character->character_id))->handle();

    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $this->test_character->character_id,
            'item_id' => $data->item_id,
        ]);
    }

    // Assert a job was pushed to a given queue...
    Queue::assertPushedOn('high', ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch ResolveUniverseTypeByIdJob if type is known', function () {
    $type = \Seatplus\Eveapi\Models\Universe\Type::factory()->create();

    $assets = Asset::factory()->count(5)->create([
        'assetable_id' => testCharacter()->character_id,
        'type_id' => $type->type_id,
    ]);

    mockRetrieveEsiDataAction($assets->toArray());

    // Run CharacterAssetsAction
    (new CharacterAssetJob($this->test_character->character_id))->handle();

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('does not dispatch ResolveLocationJob if location is known', function () {
    $location = \Seatplus\Eveapi\Models\Universe\Location::factory()->create();

    $assets = Asset::factory()->count(5)->create([
        'assetable_id' => testCharacter()->character_id,
        'location_id' => $location->location_id,
    ]);

    mockRetrieveEsiDataAction($assets->toArray());

    // Run CharacterAssetsAction
    (new CharacterAssetJob($this->test_character->character_id))->handle();

    Queue::assertNotPushed(ResolveLocationJob::class);
});

// Helpers
function buildAssetMockEsiData()
{
    $mock_data = Asset::factory()->count(5)->make([
        'assetable_id' => testCharacter()->character_id,
    ]);

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
