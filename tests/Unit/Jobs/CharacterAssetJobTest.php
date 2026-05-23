<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterAssetJob(12345);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(Asset::count())->toBe(0);
});
