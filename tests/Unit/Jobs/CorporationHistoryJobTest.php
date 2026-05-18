<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('characters->getCharactersCharacterIdCorporationhistory')->andReturn(makeEsiResult([], isCachedLoad: true));

    $job = mock(CorporationHistoryJob::class)->makePartial();
    $job->executeJob($esi);

    expect(CorporationHistory::count())->toBe(0);
});

it('has tags', function () {
    $job = new CorporationHistoryJob($character_id = 1);

    expect($job->tags())->toBe([
        'character',
        'info',
        'character_id:'.$character_id,
        'corporationhistory',
    ]);
});
