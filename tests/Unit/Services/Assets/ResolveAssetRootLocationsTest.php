<?php

use Seatplus\Eveapi\Services\Assets\ResolveAssetRootLocations;

it('resolves the root location by walking the container chain to any depth', function () {
    // 100 sits in a real location; 200 is inside 100; 300 is inside 200 (depth 3); 400 is a
    // second top-level item in the same location.
    $assets = collect([
        ['item_id' => 100, 'location_id' => 60003760],
        ['item_id' => 200, 'location_id' => 100],
        ['item_id' => 300, 'location_id' => 200],
        ['item_id' => 400, 'location_id' => 60003760],
    ]);

    $roots = (new ResolveAssetRootLocations)->resolve($assets);

    expect($roots->get(100))->toBe(60003760)
        ->and($roots->get(200))->toBe(60003760)
        ->and($roots->get(300))->toBe(60003760)
        ->and($roots->get(400))->toBe(60003760);
});

it('terminates on cyclic data instead of looping forever', function () {
    $assets = collect([
        ['item_id' => 100, 'location_id' => 200],
        ['item_id' => 200, 'location_id' => 100],
    ]);

    $roots = (new ResolveAssetRootLocations)->resolve($assets);

    expect($roots)->toHaveCount(2);
});
