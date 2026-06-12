<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Universe\Location;

beforeEach(function () {
    Queue::fake();
    Event::fake();
});

it('runs ResolveLocationService', function () {
    // Arrange
    $locationId = 100; // use low number to avoid being a potential structure or station

    $refreshToken = updateRefreshTokenScopes($this->test_character->refreshToken, ['esi-universe.read_structures.v1']);
    $refreshToken->save();

    // Act
    $job = new ResolveLocationJob($locationId, $refreshToken);

    $job->handle();

    // Assert
    // assert that mock was called
    expect(Location::all())
        ->toHaveCount(0);
});

it('returns correct tags array without refresh token', function () {
    $job = new ResolveLocationJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'location_resolve',
        'location_id:12345',
    ]);
});
