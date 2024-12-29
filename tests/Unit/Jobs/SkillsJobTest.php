<?php

it('has tags', function () {
    $job = new \Seatplus\Eveapi\Jobs\Skills\SkillsJob(1);

    expect($job->tags())->toBe([
        'skills',
        'character_id:1',
    ]);
});
