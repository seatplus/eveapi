<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Seatplus\Eveapi\Http\Resources\Group as GroupResource;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Tests\TestCase;

class GroupResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {
        $resource = (new GroupResource($group = factory(Group::class)->create()))->jsonSerialize();

        Assert::assertArraySubset([
            'name' => $group->name
        ], $resource);

    }

}
