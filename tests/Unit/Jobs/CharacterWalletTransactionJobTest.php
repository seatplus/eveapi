<?php

use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;

it('returns correct tags array for character wallet transaction job', function () {
    $job = new CharacterWalletTransactionJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'character',
        'character_id:12345',
        'wallet',
        'transaction',
    ]);
});
