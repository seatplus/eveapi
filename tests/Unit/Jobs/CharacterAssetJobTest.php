<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('checks if the response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('assets->getCharactersCharacterIdAssets')->andReturn(makeEsiResult([], isCachedLoad: true));

    $job = mock(CharacterAssetJob::class)->makePartial();
    $job->executeJob($esi);

    expect(Asset::count())->toBe(0);
});
