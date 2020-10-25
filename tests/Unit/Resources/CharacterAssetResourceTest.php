<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Seatplus\Eveapi\Http\Resources\CharacterAsset as CharacterAssetRessource;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterAssetResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {
        $resource = (new CharacterAssetRessource($character_asset = factory(CharacterAsset::class)->states('withoutType')->create()));

        $this->assertTrue($resource instanceof CharacterAssetRessource);
        $this->assertEquals($character_asset->location_id, $resource->location_id);
        $this->assertEquals($character_asset->name, $resource->name);

    }

}
