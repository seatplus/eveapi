<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Faker\Factory;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

class UniverseSystemModelTest extends TestCase
{

    /** @test */
    public function it_has_constellation()
    {

        $system = factory(System::class)->create();

        $this->assertInstanceOf(Constellation::class, $system->constellation);
    }

    /** @test */
    public function it_has_stations()
    {

        $system = factory(System::class)->states(['withStation', 'withStructure'])->create();

        $this->assertInstanceOf(Structure::class, $system->structures->first());
    }

}
