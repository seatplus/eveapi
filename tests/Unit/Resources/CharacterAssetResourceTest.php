<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Http\Resources\AssetResource;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {


        $resource = (new AssetResource($character_asset = Event::fakeFor( fn () =>Asset::factory()->create())));

        $this->assertTrue($resource instanceof AssetResource);
        $this->assertEquals($character_asset->location_id, $resource->location_id);
        $this->assertEquals($character_asset->name, $resource->name);

    }

}
