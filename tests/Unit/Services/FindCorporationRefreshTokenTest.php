<?php

use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

it('returns no RefreshToken if character has no role', function () {

    // Arrange
    $corporation_id = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';
    $role = 'Director';

    updateRefreshTokenScopes(testCharacter()->refresh_token, [$scope])->save();

    // Act
    $find_corporation_refresh_token = new FindCorporationRefreshToken;

    $refresh_token = $find_corporation_refresh_token($corporation_id, $scope, $role);

    // Assert
    expect($refresh_token)->toBeNull();
});
