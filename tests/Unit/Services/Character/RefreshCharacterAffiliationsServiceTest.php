<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Services\Character\RefreshCharacterAffiliationsService;
use Seatplus\Eveapi\Services\Jobs\CacheCharacterAffiliationIdsService;

beforeEach(function () {
    Queue::fake();

    CharacterAffiliation::query()->whereNotIn('character_id', [testCharacter()->character_id])->delete();
    CharacterInfo::query()->whereNotIn('character_id', [testCharacter()->character_id])->delete();

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
        $service = new RefreshCharacterAffiliationsService;
        $service();

        // assert
        Queue::assertPushed(CharacterAffiliationJob::class);

        // assert that the job was dispatched with the correct id
        Queue::assertPushed(CharacterAffiliationJob::class, fn (CharacterAffiliationJob $job) => in_array(testCharacter()->character_id, $job->getManualIds()));

    });

    test('cached ids', function () {

        // arrange

        // clear the redis cache
        Redis::flushall();

        // cache the character id
        CacheCharacterAffiliationIdsService::make()
            ->queue(testCharacter()->character_id);

        // act
        $service = new RefreshCharacterAffiliationsService;
        $service();

        // assert
        Queue::assertPushed(CharacterAffiliationJob::class);

        // assert that the job was dispatched with the correct id
        Queue::assertPushed(CharacterAffiliationJob::class, fn (CharacterAffiliationJob $job) => in_array(testCharacter()->character_id, $job->getManualIds()));

    });

    test('missing ids from character info', function () {

        // arrange

        // delete the character affiliation
        CharacterAffiliation::query()->delete();

        // act
        $service = new RefreshCharacterAffiliationsService;
        $service();

        // assert
        Queue::assertPushed(CharacterAffiliationJob::class);

        // assert that the job was dispatched with the correct id
        Queue::assertPushed(CharacterAffiliationJob::class, fn (CharacterAffiliationJob $job) => in_array(testCharacter()->character_id, $job->getManualIds()));

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
        $service = new RefreshCharacterAffiliationsService;
        $service();

        // assert
        Queue::assertNotPushed(CharacterAffiliationJob::class);
    });

});
