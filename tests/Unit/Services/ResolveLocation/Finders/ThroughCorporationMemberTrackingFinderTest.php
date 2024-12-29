<?php

use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughCorporationMemberTrackingFinder;

it('finds Director Token', function () {

    \Illuminate\Support\Facades\Event::fake();

    // arrange
    $scope = 'esi-corporations.track_members.v1';
    $role = 'Director';

    $refresh_token = testCharacter()->refresh_token;
    updateRefreshTokenScopes($refresh_token, [$scope])->save();

    $character_roles = $refresh_token->refresh()->character->roles;
    $character_roles->roles = [$role];
    $character_roles->save();

    $corporation_member_tracking = CorporationMemberTracking::factory()->create([
        'corporation_id' => $refresh_token->corporation_id,
        'location_id' => 1,
    ]);

    expect($refresh_token->hasScope($scope))->toBeTrue()
        ->and($refresh_token->character->roles->hasRole('roles', $role))->toBeTrue();

    $tracking = \Illuminate\Database\Eloquent\Collection::make();

    // act
    $finder = new ThroughCorporationMemberTrackingFinder;
    $result = $finder->handle($corporation_member_tracking->location_id, $tracking);

    // assert
    expect($result)->not()->toBeNull();
});
