<?php

namespace Seatplus\Eveapi\Tests\Unit\Containers;


use Seatplus\Eveapi\Containers\EsiRequest;
use Seatplus\Eveapi\Exceptions\InvalidEsiRequestDataException;
use Seatplus\Eveapi\Tests\TestCase;

class EsiRequestContainerTest extends TestCase
{
    /** @test */
    public function canSetProppertyTest()
    {
        $esi_request = new EsiRequest([
            'version' => 'v4'
        ]);

        $this->assertEquals('v4',$esi_request->version);
    }

    /** @test
     * @throws \Seatplus\Eveapi\Exceptions\InvalidEsiRequestDataException
     */
    public function canNotSetProppertyTest()
    {

        $this->expectException(InvalidEsiRequestDataException::class);

        $esi_request = new EsiRequest([
            'herpaderp' => 'v4'
        ]);

    }

    /** @test */
    public function getPublicEsiRequest()
    {

        $esi_request = new EsiRequest([
            'refresh_token' => 'someXYToken'
        ]);

        $this->assertFalse($esi_request->isPublic());

    }

}