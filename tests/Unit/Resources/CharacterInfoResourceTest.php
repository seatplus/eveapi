<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Seatplus\Eveapi\Http\Resources\AllianceInfoResource;
use Seatplus\Eveapi\Http\Resources\CharacterInfoResource;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {
        $resource = (new CharacterInfoResource($character_info = factory(CharacterInfo::class)->create()))->jsonSerialize();

        Assert::assertArraySubset([
            'character_id' => $character_info->character_id,
            'name' => $character_info->name
        ], $resource);

    }

}
