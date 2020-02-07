<?php


namespace Seatplus\Eveapi\Tests\Unit\Controllers;


use Seatplus\Eveapi\Tests\TestCase;

class AllianceControllerTest extends TestCase
{
    /** @test */
    public function it_gets_corporation_info()
    {
        $this->get(route('get.alliance_info'))
            ->assertOk();

    }

}
