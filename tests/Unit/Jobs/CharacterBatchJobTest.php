<?php

use Illuminate\Bus\Batch;
use Seatplus\Eveapi\Jobs\Seatplus\Batch\CharacterBatchJob;
use Seatplus\Eveapi\Models\BatchStatistic;
use Seatplus\Eveapi\Models\BatchUpdate;

it('discards update if still pending', function () {
    $batch_update = new BatchUpdate;

    $batch_update->is_pending = true;
    $batch_update->started_at = now();

    $job = mock(CharacterBatchJob::class)->makePartial();
    $job->shouldReceive('getBatchUpdate')->andReturn($batch_update);

    $job->handle();

    expect(BatchStatistic::all())->toHaveCount(0);
});

it('finally creates BatchStatistices', function () {

    // Arrange
    // create an very old BatchUpdate
    BatchUpdate::create([
        'batchable_id' => testCharacter()->character_id,
        'batchable_type' => \Seatplus\Eveapi\Models\Character\CharacterInfo::class,
        'started_at' => now()->subDays(2),
        'finished_at' => now()->subDays(1),
    ]);

    $job = new CharacterBatchJob(testCharacter()->character_id, batch_jobs: [fn () => 'test']);

    \Illuminate\Support\Facades\Bus::fake();

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

it('does not add AllianceContactsJob if no alliance_id is present', function () {

    // Arrange

    // make sure refresh token has esi-alliances.read_contacts.v1 scope
    $refresh_token = updateRefreshTokenScopes(testCharacter()->refresh_token, ['esi-alliances.read_contacts.v1']);
    \Illuminate\Support\Facades\Event::fakeFor(fn () => $refresh_token->save());

    // delete alliance_id from character info
    $character_affiliation = testCharacter()->character_affiliation;
    $character_affiliation->alliance_id = null;

    \Illuminate\Support\Facades\Event::fakeFor(fn () => $character_affiliation->save());

    // Act
    $job = new CharacterBatchJob($character_affiliation->character_id);

    // Assert

    expect($character_affiliation->refresh()->alliance_id)->toBeNull();

    $reflection = new ReflectionClass($job);
    // get the protected property batch_jobs
    $property = $reflection->getProperty('batch_jobs');
    $property->setAccessible(true);

    $batch_jobs = $property->getValue($job);

    // flatten the array with nested arrays
    $batch_jobs = Arr::flatten($batch_jobs);

    foreach ($batch_jobs as $batch_job) {
        expect($batch_job)->not->toBeInstanceOf(\Seatplus\Eveapi\Jobs\Contacts\AllianceContactJob::class);
    }
});

it('has middleware', function () {
    $job = new CharacterBatchJob(testCharacter()->character_id);

    expect($job->middleware())->toBeArray();
});
