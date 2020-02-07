<?php


namespace Seatplus\Eveapi\Tests\Unit\Controllers;


use Seatplus\Eveapi\Tests\TestCase;

class CharacterControllerTest extends TestCase
{
    /** @test */
    public function it_gets_character_info()
    {
        $this->get(route('get.character_info'))
            ->assertOk();

    }

}
