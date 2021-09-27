<?php

use Seatplus\Eveapi\Containers\EsiRequestContainer;
use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('setRequestBody method', function () {
    $esi_request = new EsiRequestContainer([
        'method' => '-',
        'version' => '-',
        'endpoint' => '-',
    ]);

    expect($esi_request->request_body)->toBeArray()->toBeEmpty();

    $esi_request->setRequestBody(['foo' => 'bar']);

    expect($esi_request->request_body)->toBeArray()->toBe(['foo' => 'bar']);
});


test('isPublic method', function () {
    $esi_request = new EsiRequestContainer([
        'method' => '-',
        'version' => '-',
        'endpoint' => '-',
        'refresh_token' => RefreshToken::factory()->make(),
    ]);

    expect($esi_request->isPublic())->toBeFalse();
});
