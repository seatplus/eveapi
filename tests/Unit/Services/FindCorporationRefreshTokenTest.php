<?php

use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

it('returns no RefreshToken if character has no role', function () {
    $corporationId = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';
    $role = 'Director';

    updateRefreshTokenScopes(testCharacter()->refreshToken, [$scope])->save();

    $refreshToken = (new FindCorporationRefreshToken)($corporationId, $scope, $role);

    expect($refreshToken)->toBeNull();
});

it('returns RefreshToken when character has matching scope and role', function () {
    $corporationId = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';

    updateRefreshTokenScopes(testCharacter()->refreshToken, [$scope])->save();

    CharacterRole::updateOrCreate(
        ['character_id' => testCharacter()->character_id],
        ['roles' => ['Director']],
    );

    $refreshToken = (new FindCorporationRefreshToken)($corporationId, $scope, 'Director');

    expect($refreshToken)->not->toBeNull();
});

it('returns RefreshToken when roles array is empty', function () {
    $corporationId = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';

    updateRefreshTokenScopes(testCharacter()->refreshToken, [$scope])->save();

    $refreshToken = (new FindCorporationRefreshToken)($corporationId, $scope, []);

    expect($refreshToken)->not->toBeNull();
});

it('returns null when no token has the required scope', function () {
    $corporationId = testCharacter()->corporation_id;

    $refreshToken = (new FindCorporationRefreshToken)(
        $corporationId,
        'esi-some.scope.that.does.not.exist.v1',
        []
    );

    expect($refreshToken)->toBeNull();
});
