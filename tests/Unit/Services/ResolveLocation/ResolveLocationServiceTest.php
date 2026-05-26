<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Services\ResolveLocation\ResolveLocationService;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\ResolverInterface;

it('runs through resolvers', function () {
    Event::fake();
    Queue::fake();

    // Arrange
    $location_id = 100; // use low number to avoid being a potential structure or station

    ResolveLocationService::make()->handle($location_id);

    expect(Location::count())->toBe(0);
});

it('breaks the loop on successful resolution', function () {
    $location_id = 12345;

    // Mock the Location model
    $locationMock = mock(Location::class)->makePartial();
    $locationMock->shouldReceive('with')->andReturnSelf();
    $locationMock->shouldReceive('firstOrNew')->andReturn($locationMock);

    // Mock the ResolverInterface
    $resolverMock = mock(ResolverInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('handle')->once()->andReturn(true);
    });

    // Create the service with the mocked dependencies
    $service = Mockery::mock(ResolveLocationService::class, [testCharacter()->refresh_token, [$resolverMock]])
        ->makePartial();

    // Call the handle method
    $service->handle($location_id);
});
