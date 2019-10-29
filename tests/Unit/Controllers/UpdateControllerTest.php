<?php

namespace Seatplus\Eveapi\Tests\Unit\Controllers;

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfo;
use Seatplus\Eveapi\Jobs\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    /** @test */
    public function dispatchCharacterInfoJobTest()
    {

        Bus::fake();

        $response = $this->post(route('update.character_info'),['character_id' => 1234]);

        Bus::assertDispatched(CharacterInfo::class, function ($job) {
            return $job->character_id = 1234;
        });

        $response->assertStatus(200);
    }

    /** @test */
    public function dispatchAllianceInfoJobTest()
    {

        Bus::fake();

        $response = $this->post(route('update.alliance_info'),['alliance_id' => 1234]);

        Bus::assertDispatched(AllianceInfo::class, function ($job) {
            return $job->alliance_id = 1234;
        });

        $response->assertStatus(200);
    }

}
