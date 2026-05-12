<?php

use Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('returns early if batch is cancelled', function () {
    $job = mock(CharacterAssetsNameJob::class)->makePartial();

    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob();

    expect(Asset::count())->toBe(0);
});
