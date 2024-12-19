<?php

use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;

it('returns correct tags array for character wallet journal job', function () {
    $job = new CharacterWalletJournalJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'character',
        'character_id: 12345',
        'wallet',
        'journal',
    ]);
});
