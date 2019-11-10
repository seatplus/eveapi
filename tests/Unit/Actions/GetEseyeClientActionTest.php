<?php

namespace Seatplus\Eveapi\Tests\Unit\Actions;

use Seat\Eseye\Eseye;
use Seatplus\Eveapi\Actions\Eseye\GetEseyeClientAction;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class GetEseyeClientActionTest extends TestCase
{
    protected $refresh_token;

    protected function setUp(): void
    {

        parent::setUp();

        $this->refresh_token = factory(RefreshToken::class)->create([
            'expires_on' => now()->addMinutes(5)
        ]);


    }

    /** @test */
    public function getClientForNullRefreshToken()
    {

        $action = new GetEseyeClientAction;

        $this->assertInstanceOf(Eseye::class, $action->execute());
    }

    /** @test */
    public function getClient()
    {

        $action = new GetEseyeClientAction;

        $this->assertInstanceOf(Eseye::class, $action->execute());
    }

}
