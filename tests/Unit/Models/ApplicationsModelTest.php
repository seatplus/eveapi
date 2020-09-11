<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;


use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Tests\TestCase;

class ApplicationsModelTest extends TestCase
{
    /** @test */
    public function character_has_application_relationship()
    {

        $application = factory(Applications::class)->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id
        ]);

        $this->assertInstanceOf(CharacterInfo::class, $application->applicationable);
    }

    /** @test */
    public function it_has_corporation_relationship()
    {
        $application = factory(Applications::class)->create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'applicationable_type' => CharacterInfo::class,
            'applicationable_id' => $this->test_character->character_id
        ]);

        $this->assertInstanceOf(CorporationInfo::class, $application->corporation);
    }

}
