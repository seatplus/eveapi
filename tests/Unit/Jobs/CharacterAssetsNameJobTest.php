<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('returns early if batch is cancelled', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = mock(CharacterAssetsNameJob::class)->shouldAllowMockingProtectedMethods()->makePartial();

    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob($esi);

    expect(Asset::count())->toBe(0);
});
