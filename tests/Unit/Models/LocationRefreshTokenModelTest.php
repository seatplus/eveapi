<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\LocationRefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;

beforeEach(function () {
    Event::fake();
});

it('belongs to a location', function () {
    $location = Location::factory()->create();

    $locationRefreshToken = LocationRefreshToken::query()
        ->updateOrCreate([
            'location_id' => $location->location_id,
            'character_id' => test()->test_character->character_id,
        ], [
            'resolved' => true,
        ]);

    expect($locationRefreshToken->location->is($location))->toBeTrue();
});

it('belongs to a refresh token', function () {
    $location = Location::factory()->create();

    $locationRefreshToken = LocationRefreshToken::query()
        ->updateOrCreate([
            'location_id' => $location->location_id,
            'character_id' => testCharacter()->character_id,
        ], [
            'resolved' => true,
        ]);

    expect($locationRefreshToken->refreshToken->character_id)->toBe(testCharacter()->character_id);
});
