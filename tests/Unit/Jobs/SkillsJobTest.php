<?php

use Seatplus\Eveapi\Jobs\Skills\SkillsJob;

it('has tags', function () {
    $job = new SkillsJob(1);

    expect($job->tags())->toBe([
        'skills',
        'character_id:1',
    ]);
});
