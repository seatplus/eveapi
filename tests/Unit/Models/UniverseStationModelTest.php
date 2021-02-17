<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

class UniverseStationModelTest extends TestCase
{

    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $station = Station::factory()->create();

        $this->assertInstanceOf(System::class, $station->system);
    }

}
