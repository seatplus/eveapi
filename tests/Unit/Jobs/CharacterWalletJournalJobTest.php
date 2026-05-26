<?php

use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Models\RefreshToken;

it('returns correct tags array for character wallet journal job', function () {
    $job = new CharacterWalletJournalJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'character',
        'character_id:12345',
        'wallet',
        'journal',
    ]);
});

it('returns the refresh token', function () {
    $token = RefreshToken::factory()->create();

    $job = new CharacterWalletJournalJob($token->character_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});
