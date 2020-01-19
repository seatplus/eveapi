<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Seatplus\Eveapi\Http\Resources\Type as TypeResource;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class TypeResourceTest extends TestCase
{
    public function testCorrectDataIsReturnedInResponse()
    {
        $resource = (new TypeResource($type = factory(Type::class)->create()))->jsonSerialize();

        Assert::assertArraySubset([
            'name' => $type->name
        ], $resource);

    }

}
