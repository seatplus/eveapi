<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Http\Resources\AllianceInfoResource;
use Seatplus\Eveapi\Http\Resources\CorporationInfoResource;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class CorporationInfoResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {

        $resource = (new CorporationInfoResource($corporation_info = Event::fakeFor( fn () => CorporationInfo::factory()->create())));

        $this->assertTrue($resource instanceof CorporationInfoResource);
        $this->assertEquals($corporation_info->corporation_id, $resource->corporation_id);
        $this->assertEquals($corporation_info->name, $resource->name);

    }

}
