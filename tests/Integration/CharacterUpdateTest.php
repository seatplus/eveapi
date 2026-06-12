<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

test('if constructor receives single refresh token push update to high queue', function () {
    Queue::fake();

    (new UpdateCharacter(testCharacter()->refreshToken))->handle();

    Queue::assertPushedOn('high', CharacterBatchJob::class);
});

it('dispatches character batch job for never-updated character', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    expect(RefreshToken::all())->toHaveCount(1);

    (new UpdateCharacter)->handle();

    Queue::assertPushedOn('default', CharacterBatchJob::class);
});

it('dispatches character batch job for stale character', function () {
    Queue::fake();

    // Create a stale batch update (finished long ago)
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => CharacterInfo::class,
        'started_at' => now()->subHour(),
        'finished_at' => now()->subMinutes(CharacterBatchJob::REFRESH_DELAY_MINUTES * 3),
    ]);

    (new UpdateCharacter)->handle();

    Queue::assertPushedOn('default', CharacterBatchJob::class);
});

it('does not dispatch for currently pending character', function () {
    Queue::fake();

    // Pending: started but not finished
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => CharacterInfo::class,
        'started_at' => now()->subMinutes(1),
        'finished_at' => null,
    ]);

    (new UpdateCharacter)->handle();

    Queue::assertNotPushed(CharacterBatchJob::class);
});

it('does not dispatch for recently finished character', function () {
    Queue::fake();

    // Finished recently — within 2× REFRESH_DELAY_MINUTES
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => CharacterInfo::class,
        'started_at' => now()->subMinutes(3),
        'finished_at' => now()->subMinutes(1),
    ]);

    (new UpdateCharacter)->handle();

    Queue::assertNotPushed(CharacterBatchJob::class);
});

it('dispatches with reschedule flag set', function () {
    Queue::fake();

    (new UpdateCharacter)->handle();

    Queue::assertPushed(CharacterBatchJob::class, fn ($job) => $job->reschedule === true);
});
