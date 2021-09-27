<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Services\Jobs\AssetCleanupAction;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Queue::fake();
});

test('item is present if know', function () {
    $asset = Asset::factory()->create();

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
    ]);
});

test('item is deleted if unknown', function () {
    $asset = Asset::factory()->create();

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
    ]);

    (new AssetCleanupAction)->execute($asset->assetable_id, []);

    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $asset->assetable_id,
        'item_id' => $asset->item_id,
    ]);
});

it('does not delete items that dont belong to character', function () {
    $assets = Asset::factory()->count(2)->create();

    foreach ($assets as $asset) {
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
        ]);
    }

    $first_element = $assets->first();
    $second_element = $assets->last();

    // Pretend to only know the first item
    (new AssetCleanupAction)->execute($first_element->assetable_id, [$first_element->item_id]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $first_element->assetable_id,
        'item_id' => $first_element->item_id,
    ]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $second_element->assetable_id,
        'item_id' => $second_element->item_id,
    ]);
});

it('does delete items that do belong to character', function () {
    $assets = Asset::factory()->count(2)->create();

    foreach ($assets as $asset) {
        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id,
        ]);
    }

    $first_element = $assets->first();
    $second_element = $assets->last();

    // Pretend to only know the first item
    (new AssetCleanupAction)->execute($first_element->assetable_id, []);

    $this->assertDatabaseMissing('assets', [
        'assetable_id' => $first_element->assetable_id,
        'item_id' => $first_element->item_id,
    ]);

    $this->assertDatabaseHas('assets', [
        'assetable_id' => $second_element->assetable_id,
        'item_id' => $second_element->item_id,
    ]);
});
