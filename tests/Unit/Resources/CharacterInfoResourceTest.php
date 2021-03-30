<?php


namespace Seatplus\Eveapi\Tests\Unit\Resources;


use DMS\PHPUnitExtensions\ArraySubset\Assert;
use Illuminate\Support\Facades\Event;
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
        $resource = (new CharacterInfoResource($character_info = Event::fakeFor( fn () => CharacterInfo::factory()->create())));

        $this->assertTrue($resource instanceof CharacterInfoResource);
        $this->assertEquals($character_info->character_id, $resource->character_id);
        $this->assertEquals($character_info->name, $resource->name);

    }

}
