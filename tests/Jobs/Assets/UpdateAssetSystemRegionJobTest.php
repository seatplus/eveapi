<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Jobs\Assets\EnrichAssetTypeGroupCategoryJob;
use Seatplus\Eveapi\Jobs\Assets\UpdateAssetSystemRegionJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Category;
use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Type;

it('does update asset system with or without assetable_id: ', function (bool $withAssetableId) {

    // Arrange
    $station = Event::fakeFor(fn () => Station::factory()->create());

    $location = Event::fakeFor(fn () => Location::factory()->create([
        'location_id' => $station->station_id,
        'locatable_id' => $station->station_id,
        'locatable_type' => Station::class,
    ]));

    $asset = Event::fakeFor(fn () => Asset::factory()->create([
        'location_id' => $location->location_id,
        'location_type' => 'station',
    ]));

    // Act
    if ($withAssetableId) {
        (new UpdateAssetSystemRegionJob($asset->assetable_id))->handle();
    } else {
        (new UpdateAssetSystemRegionJob)->handle();
    }

    // Assert
    $assets = Asset::all();

    expect($assets->count())->toBe(1);

    expect($assets->first())
        ->solar_system_id->toBe($station->system->system_id)
        ->region_id->toBe($station->system->region->region_id);
})->with([true, false]);


