<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;


use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Actions\Seatplus\GetMissingTypeNamesAction;
use Seatplus\Eveapi\Jobs\Seatplus\GetMissingTypeNamesJob;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Tests\TestCase;

class GetMissingTypeNamesActionTest extends TestCase
{
    /** @test */
    public function it_gets_missing_type_realtionships()
    {
        $asset = factory(CharacterAsset::class)->create();

        //Assert that test character is now created
        $this->assertDatabaseHas('character_assets', [
            'item_id' => $asset->item_id
        ]);

        Bus::fake();

        (new GetMissingTypeNamesAction)->execute();

        Bus::assertDispatched(GetMissingTypeNamesJob::class);
    }

}
