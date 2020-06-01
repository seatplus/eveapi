<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Http\Resources\Type as TypeResource;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class TypeResourceTest extends TestCase
{
    /** @test */
    public function testCorrectDataIsReturnedInResponse()
    {

        $type = Event::fakeFor(fn () => factory(Type::class)->create());

        $resource = (new TypeResource($type))->jsonSerialize();

        Assert::assertArraySubset([
            'name' => $type->name
        ], $resource);

    }

}
