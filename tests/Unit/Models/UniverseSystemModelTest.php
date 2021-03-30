<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\Region;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

class UniverseSystemModelTest extends TestCase
{
    public function setUp(): void
    {

        parent::setUp();

    }

    /** @test */
    public function it_has_constellation()
    {

        $system = System::factory()->create();

        $this->assertInstanceOf(Constellation::class, $system->constellation);
    }

    /** @test */
    public function it_has_stations()
    {

        $system = System::factory()
            ->hasStations(1)
            ->hasStructures(1)
            ->create();

        $this->assertInstanceOf(Structure::class, $system->structures->first());
    }

    /** @test */
    public function it_has_region()
    {

        $system = System::factory()->create();

        $this->assertInstanceOf(Region::class, $system->constellation->region);

        $this->assertInstanceOf(Region::class, $system->region);
    }

}
