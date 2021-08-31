<?php

use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('can set propperty test', function () {
    $esi_request = new EsiRequestContainer([
        'version' => 'v4',
    ]);

    $this->assertEquals('v4', $esi_request->version);
});

test('can not set propperty test', function () {
    $this->expectException(InvalidContainerDataException::class);

    $esi_request = new EsiRequestContainer([
        'herpaderp' => 'v4',
    ]);
});

test('get public esi request', function () {
    $esi_request = new EsiRequestContainer([
        'refresh_token' => 'someXYToken',
    ]);

    $this->assertFalse($esi_request->isPublic());
});
