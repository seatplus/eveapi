<?php

use Seatplus\Eveapi\Models\BatchStatistic;

it('has asset relationship', function () {
    $batch_statistic = BatchStatistic::factory()->create([
        'assetable_id' => $this->test_character->character_id,
        'assetable_type' => CharacterInfo::class,
    ]);

    expect($this->test_character->refresh()->assets->first())->toBeInstanceOf(Asset::class);
})->skip();

it('has duration attribute', function () {
    $batch_statistic = BatchStatistic::factory()->create([
        'started_at' => now(),
        'finished_at' => now()->addMinutes(5),
    ]);

    expect($batch_statistic->duration)->toBe('5 minutes');
})->skip();
