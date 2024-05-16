<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    Queue::fake();
    Event::fake();
});

it('runs ResolveLocationService', function () {
    // Arrange
    $location_id = 100; // use low number to avoid being a potential structure or station

    $refresh_token = updateRefreshTokenScopes($this->test_character->refresh_token, ['esi-universe.read_structures.v1']);
    $refresh_token->save();

    // Act
    $job = new ResolveLocationJob($location_id, $refresh_token);

    $job->handle();

    // Assert
    //assert that mock was called
    expect(\Seatplus\Eveapi\Models\Universe\Location::all())
        ->toHaveCount(0);
});
