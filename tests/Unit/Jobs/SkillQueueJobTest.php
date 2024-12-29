<?php

it('has tags', function () {
    $job = new \Seatplus\Eveapi\Jobs\Skills\SkillQueueJob(1);

    expect($job->tags())->toBe([
        'skill queue',
        'character_id:1',
    ]);
});
