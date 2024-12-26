<?php

it('returns early if batch is cancelled', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\Assets\CharacterAssetsNameJob::class)->makePartial();

    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob();

    expect(\Seatplus\Eveapi\Models\Assets\Asset::count())->toBe(0);
});
