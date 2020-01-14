<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Faker\Factory;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

class UniverseStationModelTest extends TestCase
{

    /** @test */
    public function characterHasCorporationRelationTest()
    {

        $station = factory(Station::class)->create();

        $this->assertInstanceOf(System::class, $station->system);
    }

}
