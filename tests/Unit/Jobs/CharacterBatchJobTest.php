<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Models\BatchStatistic;
use Seatplus\Eveapi\Models\BatchUpdate;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

it('discards update if still pending', function () {
    $batchUpdate = new BatchUpdate;

    $batchUpdate->is_pending = true;
    $batchUpdate->started_at = now();

    $job = mock(CharacterBatchJob::class)->makePartial();
    $job->shouldReceive('getBatchUpdate')->andReturn($batchUpdate);

    $job->handle();

    expect(BatchStatistic::all())->toHaveCount(0);
});

it('does not discard update if finished long ago', function () {
    // Finished longer than REFRESH_DELAY_MINUTES ago — should NOT be discarded
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => CharacterInfo::class,
        'started_at' => now()->subHour(),
        'finished_at' => now()->subHour(),
    ]);

    $job = new CharacterBatchJob(testCharacter()->character_id, batchJobs: [fn () => 'test']);

    Bus::fake();

    $job->handle();

    // Job was not discarded — a batch was dispatched
    Bus::assertBatched(fn ($batch) => true);
});

it('finally creates BatchStatistices', function () {

    // Arrange
    // create an very old BatchUpdate
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => CharacterInfo::class,
        'started_at' => now()->subDays(2),
        'finished_at' => now()->subDays(1),
    ]);

    $job = new CharacterBatchJob(testCharacter()->character_id, batchJobs: [fn () => 'test']);

    Bus::fake();

    // Act
    $job->handle();

    // Assert
    Bus::assertBatched(function ($batch) {
        $batch->finally(function ($batch) {
            BatchUpdate::where('batch_id', $batch->id)->update(['finished_at' => now()]);
            BatchStatistic::where('batch_id', $batch->id)->update(['finished_at' => now()]);
        });

        return true;
    });

    expect(BatchStatistic::all())->toHaveCount(1)
        ->and(BatchUpdate::all())->toHaveCount(1);
});

it('stores queue on batch update', function () {
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => CharacterInfo::class,
        'started_at' => now()->subDays(2),
        'finished_at' => now()->subDays(1),
    ]);

    Bus::fake();

    (new CharacterBatchJob(testCharacter()->character_id, 'high', batchJobs: [fn () => 'test']))->handle();

    expect(BatchUpdate::first())->queue->toBe('high');
});

it('does not add AllianceContactsJob if no alliance_id is present', function () {

    // Arrange

    // make sure refresh token has esi-alliances.read_contacts.v1 scope
    $refreshToken = updateRefreshTokenScopes(testCharacter()->refreshToken, ['esi-alliances.read_contacts.v1']);
    Event::fakeFor(fn () => $refreshToken->save());

    // delete alliance_id from character info
    $characterAffiliation = testCharacter()->characterAffiliation;
    $characterAffiliation->alliance_id = null;

    Event::fakeFor(fn () => $characterAffiliation->save());

    // Act
    $job = new CharacterBatchJob($characterAffiliation->character_id);

    // Assert

    expect($characterAffiliation->refresh()->alliance_id)->toBeNull();

    $reflection = new ReflectionClass($job);
    // get the protected property batch_jobs
    $property = $reflection->getProperty('batchJobs');

    $batchJobs = $property->getValue($job);

    // flatten the array with nested arrays
    $batchJobs = Arr::flatten($batchJobs);

    foreach ($batchJobs as $batchJob) {
        expect($batchJob)->not->toBeInstanceOf(AllianceContactJob::class);
    }
});

it('has middleware', function () {
    $job = new CharacterBatchJob(testCharacter()->character_id);

    expect($job->middleware())->toBeArray();
});

it('has REFRESH_DELAY_MINUTES constant', function () {
    expect(CharacterBatchJob::REFRESH_DELAY_MINUTES)->toBe(5);
});
