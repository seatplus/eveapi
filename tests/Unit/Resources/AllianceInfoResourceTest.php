<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Seatplus\Eveapi\Http\Resources\AllianceInfoResource;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Tests\TestCase;

class AllianceInfoResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {
        $resource = (new AllianceInfoResource($alliance_info = factory(AllianceInfo::class)->create()))->jsonSerialize();

        Assert::assertArraySubset([
            'alliance_id' => $alliance_info->alliance_id,
            'name' => $alliance_info->name
        ], $resource);

    }

}
