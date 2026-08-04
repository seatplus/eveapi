<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Services\DispatchIndividualUpdate;

it('dispatches job', function (string $job) {
    $refreshToken = $this->test_character->refreshToken;
    // $job = 'character.assets';

    Queue::fake();

    new DispatchIndividualUpdate($refreshToken)->execute($job);

    $jobClass = config('eveapi.jobs')[$job];

    Queue::assertPushedOn('high', $jobClass);
})->with([
    'character.assets',
    'corporation.member_tracking',
]);
