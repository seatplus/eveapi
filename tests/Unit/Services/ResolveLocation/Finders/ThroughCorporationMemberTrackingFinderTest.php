<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughCorporationMemberTrackingFinder;

it('finds Director Token', function () {

    Event::fake();

    // arrange
    $scope = 'esi-corporations.track_members.v1';
    $role = 'Director';

    $refreshToken = testCharacter()->refreshToken;
    updateRefreshTokenScopes($refreshToken, [$scope])->save();

    $characterRoles = $refreshToken->refresh()->character->roles;
    $characterRoles->roles = [$role];
    $characterRoles->save();

    $corporationMemberTracking = CorporationMemberTracking::factory()->create([
        'corporation_id' => $refreshToken->corporation_id,
        'location_id' => 1,
    ]);

    expect($refreshToken->hasScope($scope))->toBeTrue()
        ->and($refreshToken->character->roles->hasRole('roles', $role))->toBeTrue();

    $tracking = Collection::make();

    // act
    $finder = new ThroughCorporationMemberTrackingFinder;
    $result = $finder->handle($corporationMemberTracking->location_id, $tracking);

    // assert
    expect($result)->not()->toBeNull();
});
