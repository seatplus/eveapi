<?php

use Seatplus\Eveapi\Models\BatchStatistic;

it('has duration attribute', function () {
    $batchStatistic = BatchStatistic::factory()->create([
        'started_at' => now(),
        'finished_at' => now()->addMinutes(5),
    ]);

    expect($batchStatistic->duration)->toBe(5 * 60);
});

test('BatchStatistic factory has finished state ', function () {
    $batchStatistic = BatchStatistic::factory()->finished()->create();

    expect($batchStatistic->started_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($batchStatistic->finished_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($batchStatistic->duration)->toBeGreaterThan(0);
});
