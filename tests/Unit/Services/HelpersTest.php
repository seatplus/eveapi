<?php

use Carbon\Carbon;

it('throws error if not enough arguments are provided', function () {
    setting(['test']);
})->expectExceptionMessage('Must provide a name and value when setting a setting.');

it('carbon returns now when called without argument', function () {
    expect(carbon())->toBeInstanceOf(Carbon::class);
});

it('carbon parses a date string', function () {
    $result = carbon('2021-01-15');

    expect($result)->toBeInstanceOf(Carbon::class)
        ->and($result->year)->toBe(2021)
        ->and($result->month)->toBe(1)
        ->and($result->day)->toBe(15);
});
