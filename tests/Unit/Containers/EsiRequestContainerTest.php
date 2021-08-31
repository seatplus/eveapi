<?php

use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('can set propperty test', function () {
    $esi_request = new EsiRequestContainer([
        'version' => 'v4',
    ]);

    expect($esi_request->version)->toEqual('v4');
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

    expect($esi_request->isPublic())->toBeFalse();
});
