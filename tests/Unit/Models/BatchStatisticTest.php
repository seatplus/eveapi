<?php

use Seatplus\Eveapi\Models\BatchStatistic;

it('has duration attribute', function () {
    $batch_statistic = BatchStatistic::factory()->create([
        'started_at' => now(),
        'finished_at' => now()->addMinutes(5),
    ]);

    expect($batch_statistic->duration)->toBe(5 * 60);
});

test('BatchStatistic factory has finished state ', function () {
    $batch_statistic = BatchStatistic::factory()->finished()->create();

    expect($batch_statistic->started_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($batch_statistic->finished_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($batch_statistic->duration)->toBeGreaterThan(0);
});
