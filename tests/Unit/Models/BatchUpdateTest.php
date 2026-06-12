<?php

use Carbon\Carbon;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

dataset('batchUpdate', function () {
    yield fn () => BatchUpdate::create([
        'batchable_id' => CharacterInfo::first()->character_id,
        'batchable_type' => CharacterInfo::class,
    ]);
});

it('has has batchable morph to relationship', function ($batch) {
    expect($batch)->batchable->toBeInstanceOf(CharacterInfo::class);
})->with('batchUpdate');

test('character has batchUpdate relationship ', function ($batch) {
    expect(CharacterInfo::first())->batchUpdate->toBeInstanceOf(BatchUpdate::class);
})->with('batchUpdate');

it('has isPending attribute and scope', function ($batch) {
    expect($batch)->is_pending->toBeFalse();

    // check the scope
    $queryResult = BatchUpdate::query()->pending()->get();
    expect($queryResult)->toHaveCount(0);

    // make it pending by adding a start at
    $batch->started_at = now()->subMinute();
    $batch->save();

    expect($batch)->is_pending->toBeTrue();

    // now scope should return 1
    $queryResult = BatchUpdate::query()->pending()->get();
    expect($queryResult)->toHaveCount(1);

    // now finish it
    $batch->started_at = now()->subMinutes(2);
    $batch->finished_at = now();
    $batch->save();

    expect($batch)
        ->is_pending->toBeFalse()
        ->started_at->toBeInstanceOf(Carbon::class)
        ->finished_at->toBeInstanceOf(Carbon::class);

    // check the scope, should be 0
    $queryResult = BatchUpdate::query()->pending()->get();
    expect($queryResult)->toHaveCount(0);
})->with('batchUpdate');

it('filters by character scope', function (BatchUpdate $batchUpdate) {

    $result = BatchUpdate::character()->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->is($batchUpdate))->toBeTrue();
})->with('batchUpdate');
