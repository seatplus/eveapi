<?php

use Seatplus\Eveapi\Services\Assets\ResolveAssetRootLocations;

it('resolves the root location and root item by walking the container chain to any depth', function () {
    // 100 sits in a real location; 200 is inside 100; 300 is inside 200 (depth 3); 400 is a
    // second top-level item in the same location.
    $assets = collect([
        ['item_id' => 100, 'location_id' => 60003760],
        ['item_id' => 200, 'location_id' => 100],
        ['item_id' => 300, 'location_id' => 200],
        ['item_id' => 400, 'location_id' => 60003760],
    ]);

    $roots = (new ResolveAssetRootLocations)->resolve($assets);

    // root_location_id: everything rolls up to the station.
    expect($roots->get(100)['root_location_id'])->toBe(60003760)
        ->and($roots->get(200)['root_location_id'])->toBe(60003760)
        ->and($roots->get(300)['root_location_id'])->toBe(60003760)
        ->and($roots->get(400)['root_location_id'])->toBe(60003760);

    // root_item_id: the top-level item (direct child of the station) each asset lives in.
    expect($roots->get(100)['root_item_id'])->toBe(100)  // top-level → itself
        ->and($roots->get(200)['root_item_id'])->toBe(100)  // inside 100
        ->and($roots->get(300)['root_item_id'])->toBe(100)  // depth 3, still under 100
        ->and($roots->get(400)['root_item_id'])->toBe(400); // separate top-level → itself
});

it('terminates on cyclic data instead of looping forever', function () {
    $assets = collect([
        ['item_id' => 100, 'location_id' => 200],
        ['item_id' => 200, 'location_id' => 100],
    ]);

    $roots = (new ResolveAssetRootLocations)->resolve($assets);

    expect($roots)->toHaveCount(2);
});
