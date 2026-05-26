<?php

use Illuminate\Support\Facades\RateLimiter;
use Seatplus\Eveapi\Services\Esi\InvalidTokenThrottleService;

it('returns false when failures are below threshold', function () {
    $service = new InvalidTokenThrottleService;

    for ($i = 1; $i < InvalidTokenThrottleService::MAX_FAILURES; $i++) {
        expect($service->hit(12345))->toBeFalse();
    }
});

it('returns true when failures reach the threshold', function () {
    $service = new InvalidTokenThrottleService;

    for ($i = 1; $i < InvalidTokenThrottleService::MAX_FAILURES; $i++) {
        $service->hit(12345);
    }

    expect($service->hit(12345))->toBeTrue();
});

it('tracks failures independently per character', function () {
    $service = new InvalidTokenThrottleService;

    for ($i = 1; $i < InvalidTokenThrottleService::MAX_FAILURES; $i++) {
        $service->hit(11111);
    }

    expect($service->hit(11111))->toBeTrue()
        ->and($service->hit(22222))->toBeFalse();
});

it('resets after decay window', function () {
    $service = new InvalidTokenThrottleService;

    for ($i = 0; $i < InvalidTokenThrottleService::MAX_FAILURES; $i++) {
        $service->hit(99999);
    }

    RateLimiter::clear('invalid_token:99999');

    expect($service->hit(99999))->toBeFalse();
});
