<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Services\DispatchIndividualUpdate;

it('dispatches job', function () {
    $refresh_token = $this->test_character->refresh_token;
    $job = 'character.assets';

    Queue::fake();

    (new DispatchIndividualUpdate($refresh_token))->execute($job);

    $job_class = config('eveapi.jobs')[$job];

    Queue::assertPushedOn('high', $job_class);
});
