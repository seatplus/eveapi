<?php

use Seatplus\Eveapi\Services\ResolveLocation\ResolveLocationService;

it('runs through resolvers', function () {
    \Illuminate\Support\Facades\Event::fake();
    \Illuminate\Support\Facades\Queue::fake();

    // Arrange
    $location_id = 100; // use low number to avoid being a potential structure or station

    ResolveLocationService::make()->handle($location_id);

    expect(\Seatplus\Eveapi\Models\Universe\Location::count())->toBe(0);
});
