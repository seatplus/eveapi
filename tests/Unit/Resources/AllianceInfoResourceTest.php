<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use Seatplus\Eveapi\Http\Resources\AllianceInfoResource;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Tests\TestCase;

class AllianceInfoResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {
        $resource = (new AllianceInfoResource($alliance_info = AllianceInfo::factory()->create()));

        $this->assertTrue($resource instanceof AllianceInfoResource);
        $this->assertEquals($alliance_info->alliance_id, $resource->alliance_id);
        $this->assertEquals($alliance_info->name, $resource->name);
    }

}
