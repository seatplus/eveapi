<?php

use Seatplus\Eveapi\Jobs\Skills\SkillsJob;
use Seatplus\Eveapi\Models\RefreshToken;

it('has tags', function () {
    $job = new SkillsJob(1);

    expect($job->tags())->toBe([
        'character',
        'character_id:1',
        'skills',
    ]);
});

it('returns the refresh token', function () {
    $token = RefreshToken::factory()->create();

    $job = new SkillsJob($token->character_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});
