<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Faker\Factory;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\UniverseConstellationCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Events\UniverseSystemCreated;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

class UniverseStructureModelTest extends TestCase
{

    /** @test */
    public function has_system_relationship()
    {
        Event::fake([
            UniverseStructureCreated::class,
        ]);

        $structure = factory(Structure::class)->create();

        $this->assertInstanceOf(System::class, $structure->system);
    }

}
