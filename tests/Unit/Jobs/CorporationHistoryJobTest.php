<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationHistoryJob($characterId = 1);
    $job->executeJob($esi);

    expect(CorporationHistory::count())->toBe(0);
});

it('has tags', function () {
    $job = new CorporationHistoryJob($characterId = 1);

    expect($job->tags())->toBe([
        'character',
        'info',
        'character_id:'.$characterId,
        'corporationhistory',
    ]);
});
