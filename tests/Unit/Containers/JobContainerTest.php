<?php

namespace Seatplus\Eveapi\Tests\Unit\Containers;


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

class JobContainerTest extends TestCase
{
    /** @test */
    public function canSetProppertyTest()
    {
        $job = new JobContainer([
            'character_id' => 12
        ]);

        $this->assertEquals(12, $job->character_id);
    }

    /** @test
     * @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
     */
    public function canNotSetProppertyTest()
    {

        $this->expectException(InvalidContainerDataException::class);

        new JobContainer([
            'herpaderp' => 'v4'
        ]);

    }

    /** @test */
    public function getCharacterIdViaPropperty()
    {

        $job = new JobContainer([
            'character_id' => 12
        ]);

        $this->assertEquals(12, $job->getCharacterId());
    }

    /** @test */
    public function getCharacterIdViaRefreshToken()
    {
        $refresh_token = factory(RefreshToken::class)->create([
            'expires_on' => now()->addDay()
        ]);

        $job = new JobContainer([
            'refresh_token' => $refresh_token
        ]);

        $this->assertEquals($refresh_token->character_id, $job->getCharacterId());
    }

}