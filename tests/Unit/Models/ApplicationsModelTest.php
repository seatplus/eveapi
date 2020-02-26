<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class ApplicationsModelTest extends TestCase
{
    /** @test */
    public function it_has_character_relationship()
    {
        $application = factory(Applications::class)->create();

        $this->assertInstanceOf(CharacterInfo::class, $application->character);
    }

    /** @test */
    public function it_has_corporation_relationship()
    {
        $application = factory(Applications::class)->create();

        $this->assertInstanceOf(CorporationInfo::class, $application->corporation);
    }

}
