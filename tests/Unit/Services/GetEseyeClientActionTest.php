<?php

use Illuminate\Support\Facades\Event;
use Seat\Eseye\Eseye;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetEseyeClient;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Event::fakeFor(function () {
        $this->refresh_token = RefreshToken::factory()->create([
            'expires_on' => now()->addMinutes(5),
        ]);
    });
});

test('get client for null refresh token', function () {
    $action = new GetEseyeClient;

    $this->assertInstanceOf(Eseye::class, $action->execute());
});

test('get client', function () {
    $action = new GetEseyeClient;

    $this->assertInstanceOf(Eseye::class, $action->execute());
});
