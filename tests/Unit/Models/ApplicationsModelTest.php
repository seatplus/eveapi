<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Application;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class ApplicationsModelTest extends TestCase
{
    /** @test */
    public function character_has_application_relationship()
    {

        $application = Application::factory()->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id
        ]);

        $this->assertInstanceOf(CharacterInfo::class, $application->applicationable);
        $this->assertInstanceOf(Application::class, $this->test_character->application);
    }

    /** @test */
    public function it_has_corporation_relationship()
    {
        $application = Application::factory()->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id
        ]);

        $this->assertInstanceOf(CorporationInfo::class, $application->corporation);
    }

    /** @test */
    public function create_application_through_character()
    {

        $this->test_character->application()->create(['corporation_id' => $this->test_character->corporation->corporation_id]);

        $this->assertInstanceOf(Application::class, $this->test_character->application);
    }

    /** @test */
    public function has_ofCorporation_scope()
    {

        $application = Application::factory()->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id
        ]);

        $this->assertInstanceOf(CharacterInfo::class, Application::ofCorporation($this->test_character->corporation->corporation_id)->first()->applicationable);
    }

}
