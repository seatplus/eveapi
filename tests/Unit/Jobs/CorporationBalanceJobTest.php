<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\Balance;

it('returns correct tags array', function () {
    $job = new CorporationBalanceJob(12345);

    $tags = $job->tags();

    expect($tags)->toBe([
        'corporation',
        'corporation_id:12345',
        'balances',
    ]);
});

it('does not upsert balances when response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationBalanceJob(12345);
    $job->executeJob($esi);

    expect(Balance::count())->toBe(0);
});

it('returns the corporation refresh token for an accountant', function () {
    $scope = head(config('eveapi.scopes.corporation.wallet'));
    updateRefreshTokenScopes(testCharacter()->refreshToken, [$scope])->save();

    CharacterRole::updateOrCreate(
        ['character_id' => testCharacter()->character_id],
        ['roles' => ['Accountant']],
    );

    $job = new CorporationBalanceJob(testCharacter()->corporation_id);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});

it('throws when no eligible token is found for corporation balance', function () {
    $job = new CorporationBalanceJob(99999999);

    expect(fn () => $job->getRefreshToken())->toThrow(Exception::class);
});
