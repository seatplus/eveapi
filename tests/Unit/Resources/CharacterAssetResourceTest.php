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
        $resource = (new CharacterAssetRessource($character_asset = factory(CharacterAsset::class)->states('withoutType')->create()))->jsonSerialize();

        Assert::assertArraySubset([
            'location_id' => $character_asset->location_id,
            'name' => $character_asset->name
        ], $resource);

    }

}
