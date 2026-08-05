<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('returns early if batch is cancelled', function () {
    // No expectations on the client: any ESI call means the guard did not return early.
    $esi = Mockery::mock(EsiClient::class);

    [$job, $batch] = new CharacterAssetsNameJob(testCharacter()->character_id)->withFakeBatch();
    $batch->cancel();

    $job->executeJob($esi);

    expect(Asset::count())->toBe(0);
});
