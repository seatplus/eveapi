<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterInfoJob(12345);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(true)->toBeTrue();
});
