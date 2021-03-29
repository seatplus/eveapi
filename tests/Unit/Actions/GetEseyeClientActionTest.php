<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions;

use Illuminate\Support\Facades\Event;
use Seat\Eseye\Eseye;
use Seatplus\Eveapi\Services\Esi\GetEseyeClient;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class GetEseyeClientActionTest extends TestCase
{
    protected $refresh_token;

    protected function setUp(): void
    {

        parent::setUp();

        Event::fakeFor(function () {
            $this->refresh_token = RefreshToken::factory()->create([
                'expires_on' => now()->addMinutes(5)
            ]);
        });


    }

    /** @test */
    public function getClientForNullRefreshToken()
    {

        $action = new GetEseyeClient;

        $this->assertInstanceOf(Eseye::class, $action->execute());
    }

    /** @test */
    public function getClient()
    {

        $action = new GetEseyeClient;

        $this->assertInstanceOf(Eseye::class, $action->execute());
    }

}
