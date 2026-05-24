<?php

use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

it('returns no RefreshToken if character has no role', function () {
    $corporation_id = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';
    $role = 'Director';

    updateRefreshTokenScopes(testCharacter()->refresh_token, [$scope])->save();

    $refresh_token = (new FindCorporationRefreshToken)($corporation_id, $scope, $role);

    expect($refresh_token)->toBeNull();
});

it('returns RefreshToken when character has matching scope and role', function () {
    $corporation_id = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';

    updateRefreshTokenScopes(testCharacter()->refresh_token, [$scope])->save();

    CharacterRole::updateOrCreate(
        ['character_id' => testCharacter()->character_id],
        ['roles' => ['Director']],
    );

    $refresh_token = (new FindCorporationRefreshToken)($corporation_id, $scope, 'Director');

    expect($refresh_token)->not->toBeNull();
});

it('returns RefreshToken when roles array is empty', function () {
    $corporation_id = testCharacter()->corporation_id;
    $scope = 'esi-corporations.read_corporation_membership.v1';

    updateRefreshTokenScopes(testCharacter()->refresh_token, [$scope])->save();

    $refresh_token = (new FindCorporationRefreshToken)($corporation_id, $scope, []);

    expect($refresh_token)->not->toBeNull();
});

it('returns null when no token has the required scope', function () {
    $corporation_id = testCharacter()->corporation_id;

    $refresh_token = (new FindCorporationRefreshToken)(
        $corporation_id,
        'esi-some.scope.that.does.not.exist.v1',
        []
    );

    expect($refresh_token)->toBeNull();
});
