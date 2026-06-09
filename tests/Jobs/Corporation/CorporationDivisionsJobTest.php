<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\Corporation\CorporationDivision;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;

it('runs the job', function () {
    Queue::fake();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) [
        'hangar' => [
            (object) ['division' => 1, 'name' => 'Loot and Salavage'],
            (object) ['division' => 2, 'name' => 'Directors'],
            (object) ['division' => 3, 'name' => 'Capital Farm Supplies'],
            (object) ['division' => 4, 'name' => 'Member Hangar'],
            (object) ['division' => 5, 'name' => 'Member Ships'],
            (object) ['division' => 6, 'name' => 'Rolling Ships'],
            (object) ['division' => 7, 'name' => 'Common ships and modules'],
        ],
        'wallet' => [
            (object) ['division' => 1, 'name' => 'Wallet 1'],
            (object) ['division' => 2, 'name' => 'Wallet 2'],
            (object) ['division' => 3, 'name' => 'Wallet 3'],
            (object) ['division' => 4, 'name' => 'Wallet 4'],
            (object) ['division' => 5, 'name' => 'Wallet 5'],
            (object) ['division' => 6, 'name' => 'Wallet 6'],
            (object) ['division' => 7, 'name' => 'Wallet 7'],
        ],
    ]));

    expect(CorporationDivision::all())->toHaveCount(0);

    $job = new CorporationDivisionsJob(testCharacter()->corporation->corporation_id);
    $job->executeJob($esi);

    expect(CorporationDivision::all())->toHaveCount(14);

    expect(CorporationDivision::first()->corporation instanceof CorporationInfo)->toBeTrue();
});

it('returns the corporation refresh token for a director', function () {
    $scope = 'esi-corporations.read_divisions.v1';
    $token = updateRefreshTokenScopes(testCharacter()->refreshToken, [$scope]);
    $token->save();

    CharacterRole::updateOrCreate(
        ['character_id' => testCharacter()->character_id],
        ['roles' => ['Director']],
    );

    $job = new CorporationDivisionsJob(testCharacter()->corporation_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});

it('throws when no eligible token is found for corporation', function () {
    $job = new CorporationDivisionsJob(99999999);

    expect(fn () => $job->getRefreshToken())->toThrow(Exception::class);
});
