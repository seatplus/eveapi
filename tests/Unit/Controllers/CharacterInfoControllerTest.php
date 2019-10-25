<?php

namespace Seatplus\Eveapi\Tests\Unit\Controllers;

use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Character\Info;
use Seatplus\Eveapi\Tests\TestCase;

class CharacterInfoControllerTest extends TestCase
{
    /** @test */
    public function dispatchJobTest()
    {

        Bus::fake();

        $response = $this->post(route('info'),['character_id' => 1234]);

        Bus::assertDispatched(Info::class, function ($job) {
            return $job->character_id = 1234;
        });

        $response->assertStatus(200);
    }

}