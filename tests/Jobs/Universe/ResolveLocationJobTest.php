<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Events\RefreshTokenCreated;
use Seatplus\Eveapi\Events\UniverseStationCreated;
use Seatplus\Eveapi\Events\UniverseStructureCreated;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStationByIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseStructureByIdJob;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Services\ResolveLocation\ResolveLocationService;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();
    Event::fake();
});

it('runs ResolveLocationService', function () {
    // Arrange
    $location_id = 1_024_825_895_661;

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-universe.read_structures.v1']);
    $refresh_token->save();

    $mock = mock('alias:' . ResolveLocationService::class, function ($mock) use ($location_id, $refresh_token) {
        $mock->shouldReceive('make')
            ->with($refresh_token)
            ->andReturnSelf();

        $mock->shouldReceive('handle')
            ->with($location_id);
    });

    // Act
    $job = new ResolveLocationJob($location_id, $refresh_token);

    $job->handle();

    // Assert
    //assert that mock was called
    $mock->shouldHaveReceived('handle');

});


