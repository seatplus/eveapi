<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Http\Resources\AllianceInfoResource;
use Seatplus\Eveapi\Http\Resources\Group as GroupResource;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Tests\TestCase;

class GroupResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {
        $group = Event::fakeFor(fn () => Group::factory()->create());

        $resource = (new GroupResource($group));

        $this->assertTrue($resource instanceof GroupResource);
        $this->assertEquals($group->name, $resource->name);
    }

}
