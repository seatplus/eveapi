<?php

use Seatplus\Eveapi\Jobs\Skills\SkillQueueJob;
use Seatplus\Eveapi\Models\RefreshToken;

it('has tags', function () {
    $job = new SkillQueueJob(1);

    expect($job->tags())->toBe([
        'character',
        'character_id:1',
        'skillqueue',
    ]);
});

it('returns the refresh token', function () {
    $token = RefreshToken::factory()->create();

    $job = new SkillQueueJob($token->character_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});
