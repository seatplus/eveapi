<?php

use Seatplus\Eveapi\Jobs\Hydrate\Maintenance\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Models\Assets\Asset;

it('returns early if batch is cancelled', function () {
    $job = mock(EnrichAssetTypeGroupCategoryJob::class, function ($mock) {
        $mock->shouldReceive('batch->cancelled')->andReturn(true);
    })->makePartial();

    $job->handle();

    expect(Asset::count())->toBe(0);
});
