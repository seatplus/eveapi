<?php

use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;

it('has tags', function () {
    $job = new SkillQueueJob(1);

    expect($job->tags())->toBe([
        'character',
        'character_id:1',
        'skillqueue',
    ]);
});
