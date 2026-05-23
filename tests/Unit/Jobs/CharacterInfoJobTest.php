<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterInfoJob(12345);
    $job->executeJob($esi);

    expect(CharacterInfo::where('character_id', 12345)->count())->toBe(0);
});
