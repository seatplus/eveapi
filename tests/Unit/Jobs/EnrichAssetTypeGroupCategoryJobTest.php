<?php

it('returns early if batch is cancelled', function () {
    $job = mock(\Seatplus\Eveapi\Jobs\Assets\EnrichAssetTypeGroupCategoryJob::class, function ($mock) {
        $mock->shouldReceive('batch->cancelled')->andReturn(true);
    })->makePartial();

    $job->handle();

    expect(\Seatplus\Eveapi\Models\Assets\Asset::count())->toBe(0);
});
