<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;

class RefreshTokenModelTest extends TestCase
{

    /** @test */
    public function it_has_character_relationship()
    {
        $this->assertInstanceOf(CharacterInfo::class, $this->test_character->refresh_token->character);
    }

}
