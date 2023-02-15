<?php

use Seatplus\Eveapi\Models\BatchStatistic;

it('has duration attribute', function () {
    $batch_statistic = BatchStatistic::factory()->create([
        'started_at' => now(),
        'finished_at' => now()->addMinutes(5),
    ]);

    expect($batch_statistic->duration)->toBe(5 * 60);
});
