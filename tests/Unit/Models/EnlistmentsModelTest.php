<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Recruitment\Enlistments;
use Seatplus\Eveapi\Tests\TestCase;

class EnlistmentsModelTest extends TestCase
{
    /** @test */
    public function has_corporation_relationship()
    {
        $enlistment = Enlistments::create([
            'corporation_id' => $this->test_character->corporation->corporation_id,
            'type' => 'user',
        ]);

        $this->assertDatabaseHas('enlistments', ['corporation_id' => $enlistment->corporation_id]);

        $this->assertInstanceOf(CorporationInfo::class, $enlistment->corporation);
    }
}
