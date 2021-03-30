<?php


namespace Seatplus\Eveapi\Tests\Unit\Services;


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Services\Jobs\AssetCleanupAction;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Tests\TestCase;

class AssetsCleanupActionTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    /** @test */
    public function item_is_present_if_know(){

        $asset = Asset::factory()->create();

        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id
        ]);
    }

    /** @test */
    public function item_is_deleted_if_unknown(){

        $asset = Asset::factory()->create();

        $this->assertDatabaseHas('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id
        ]);

        (new AssetCleanupAction)->execute($asset->assetable_id, []);

        $this->assertDatabaseMissing('assets', [
            'assetable_id' => $asset->assetable_id,
            'item_id' => $asset->item_id
        ]);
    }

    /** @test */
    public function it_does_not_delete_items_that_dont_belong_to_character(){

        $assets = Asset::factory()->count(2)->create();

        foreach ($assets as $asset) {
            $this->assertDatabaseHas('assets', [
                'assetable_id' => $asset->assetable_id,
                'item_id' => $asset->item_id
            ]);
        }

        $first_element = $assets->first();
        $second_element = $assets->last();

        // Pretend to only know the first item
        (new AssetCleanupAction)->execute($first_element->assetable_id, [$first_element->item_id]);

        $this->assertDatabaseHas('assets', [
            'assetable_id' => $first_element->assetable_id,
            'item_id' => $first_element->item_id
        ]);

        $this->assertDatabaseHas('assets', [
            'assetable_id' => $second_element->assetable_id,
            'item_id' => $second_element->item_id
        ]);
    }

    /** @test */
    public function it_does_delete_items_that_do_belong_to_character(){

        $assets =  Asset::factory()->count(2)->create();

        foreach ($assets as $asset) {
            $this->assertDatabaseHas('assets', [
                'assetable_id' => $asset->assetable_id,
                'item_id' => $asset->item_id
            ]);
        }

        $first_element = $assets->first();
        $second_element = $assets->last();

        // Pretend to only know the first item
        (new AssetCleanupAction)->execute($first_element->assetable_id, []);

        $this->assertDatabaseMissing('assets', [
            'assetable_id' => $first_element->assetable_id,
            'item_id' => $first_element->item_id
        ]);

        $this->assertDatabaseHas('assets', [
            'assetable_id' => $second_element->assetable_id,
            'item_id' => $second_element->item_id
        ]);
    }


}
