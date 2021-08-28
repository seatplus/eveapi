<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Models\Universe\Location;
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

        $structure = Structure::factory()->create();

        $this->assertInstanceOf(System::class, $structure->system);
    }

    /** @test */
    public function has_location_relationship()
    {
        Event::fake([
            UniverseStructureCreated::class,
        ]);

        $structure = Structure::factory()->create();
        $location = Location::factory()->create([
            'location_id' => $structure->structure_id,
            'locatable_id' => $structure->structure_id,
            'locatable_type' => Structure::class,
        ]);

        $this->assertInstanceOf(Location::class, $structure->location);
    }
}
