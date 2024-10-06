<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Services\Character\RefreshCharacterAffiliationsService;

beforeEach(function () {
    Queue::fake();

    // check that only one CharacterAffiliation exists and that it is the test character
    expect(CharacterAffiliation::count())->toBe(1)
        ->and(CharacterAffiliation::first()->character_id)->toBe(testCharacter()->character_id);
});

describe('dispatches CharacterAffiliationJob for ', function () {

    test('older than an hour', function () {

        // arrange

        // update the character affiliation to not be last pulled within the last hour
        CharacterAffiliation::query()->update([
            'last_pulled' => now()->subHours(2),
        ]);

        // act
        $service = new RefreshCharacterAffiliationsService();
        $service();

        // assert
        Queue::assertPushed(CharacterAffiliationJob::class);

        // assert that the job was dispatched with the correct id
        Queue::assertPushed(CharacterAffiliationJob::class, fn(CharacterAffiliationJob $job) => $job->getManualIds() === [testCharacter()->character_id]);

    });

    test('cached ids', function () {

        // arrange

        // clear the redis cache
        \Illuminate\Support\Facades\Redis::flushall();

        // cache the character id
        \Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService::make()
            ->queue(testCharacter()->character_id);

        // act
        $service = new RefreshCharacterAffiliationsService();
        $service();

        // assert
        Queue::assertPushed(CharacterAffiliationJob::class);

        // assert that the job was dispatched with the correct id
        Queue::assertPushed(CharacterAffiliationJob::class, fn(CharacterAffiliationJob $job) => $job->getManualIds() === [testCharacter()->character_id]);

    });

    test('missing ids from character info', function () {

        // arrange

        // delete the character affiliation
        CharacterAffiliation::query()->delete();

        // act
        $service = new RefreshCharacterAffiliationsService();
        $service();

        // assert
        Queue::assertPushed(CharacterAffiliationJob::class);

        // assert that the job was dispatched with the correct id
        Queue::assertPushed(CharacterAffiliationJob::class, fn(CharacterAffiliationJob $job) => $job->getManualIds() === [testCharacter()->character_id]);

    });

});

describe('does not dispatches CharacterAffiliationJob ', function () {

    test('for younger than an hour', function () {

        // arrange

        // update the character affiliation to be pulled within the last hour
        CharacterAffiliation::query()->update([
            'last_pulled' => now()->subMinutes(30),
        ]);

        // act
        $service = new RefreshCharacterAffiliationsService();
        $service();

        // assert
        Queue::assertNotPushed(CharacterAffiliationJob::class);
    });

});
