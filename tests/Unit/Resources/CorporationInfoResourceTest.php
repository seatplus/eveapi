<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Seatplus\Eveapi\Http\Resources\CorporationInfoResource;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationInfoResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {

        $resource = (new CorporationInfoResource($corporation_info = factory(CorporationInfo::class)->create()))->jsonSerialize();

        Assert::assertArraySubset([
            'corporation_id' => $corporation_info->corporation_id,
            'name' => $corporation_info->name
        ], $resource);

    }

}
