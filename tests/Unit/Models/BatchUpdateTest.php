<?php

use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

dataset('batch_update', function () {
    yield fn () => BatchUpdate::create([
        'batchable_id' => CharacterInfo::first()->character_id,
        'batchable_type' => CharacterInfo::class,
    ]);
});

it('has has batchable morph to relationship', function ($batch) {
    expect($batch)->batchable->toBeInstanceOf(CharacterInfo::class);
})->with('batch_update');

test('character has batch_update relationship ', function ($batch) {
    expect($this->test_character)->batch_update->toBeInstanceOf(BatchUpdate::class);
})->with('batch_update');

it('has isPending attribute and scope', function ($batch) {
    expect($batch)->is_pending->toBeFalse();

    // check the scope
    $query_result = BatchUpdate::query()->pending()->get();
    expect($query_result)->toHaveCount(0);

    // make it pending by adding a start at
    $batch->started_at = now()->subMinute();
    $batch->save();

    expect($batch)->is_pending->toBeTrue();

    // now scope should return 1
    $query_result = BatchUpdate::query()->pending()->get();
    expect($query_result)->toHaveCount(1);

    // now finish it
    $batch->started_at = now()->subMinutes(2);
    $batch->finished_at = now();
    $batch->save();

    expect($batch)
        ->is_pending->toBeFalse()
        ->started_at->toBeInstanceOf(\Carbon\Carbon::class)
        ->finished_at->toBeInstanceOf(\Carbon\Carbon::class);

    // check the scope, should be 0
    $query_result = BatchUpdate::query()->pending()->get();
    expect($query_result)->toHaveCount(0);
})->with('batch_update');
