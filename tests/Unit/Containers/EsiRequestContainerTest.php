<?php

namespace Seatplus\Eveapi\Tests\Unit\Containers;


use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Tests\TestCase;

class EsiRequestContainerTest extends TestCase
{
    /** @test */
    public function canSetProppertyTest()
    {
        $esi_request = new EsiRequestContainer([
            'version' => 'v4'
        ]);

        $this->assertEquals('v4',$esi_request->version);
    }

    /** @test
     * @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
     */
    public function canNotSetProppertyTest()
    {

        $this->expectException(InvalidContainerDataException::class);

        $esi_request = new EsiRequestContainer([
            'herpaderp' => 'v4'
        ]);

    }

    /** @test */
    public function getPublicEsiRequest()
    {

        $esi_request = new EsiRequestContainer([
            'refresh_token' => 'someXYToken'
        ]);

        $this->assertFalse($esi_request->isPublic());

    }

}