<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CorporationsCorporationIdWalletsDivisionJournalGetItem;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalByDivisionJob;
use Seatplus\Eveapi\Models\Character\CharacterRole;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

it('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CorporationWalletJournalByDivisionJob(corporation_id: 456, division: 1);
    $job->executeJob($esi);

    expect(WalletJournal::count())->toBe(0);
});

it('handles multiple pages and writes all journal entries', function () {
    $entry = fn (int $id) => CorporationsCorporationIdWalletsDivisionJournalGetItem::from((object) [
        'id' => $id,
        'date' => now()->toIso8601String(),
        'description' => 'test',
        'ref_type' => 'player_trading',
        'amount' => 100.0,
        'balance' => 200.0,
        'context_id' => 11111,
        'context_id_type' => 'character_id',
    ]);

    $page1 = makeEsiRawResponse(makeEsiResult([$entry(111)], pages: 2));
    $page2 = makeEsiRawResponse(makeEsiResult([$entry(222)], pages: 2));

    $esi = Mockery::mock(EsiClient::class);
    $esi->shouldReceive('withToken')->andReturnSelf();
    $esi->shouldReceive('assertScope')->andReturnNull();
    $esi->shouldReceive('invoke')->andReturn($page1, $page2);

    $job = new CorporationWalletJournalByDivisionJob(corporation_id: 456, division: 1);
    $job->executeJob($esi);

    expect(WalletJournal::count())->toBe(2);
});

it('returns the corporation refresh token for an accountant', function () {
    $scope = head(config('eveapi.scopes.corporation.wallet'));
    updateRefreshTokenScopes(testCharacter()->refresh_token, [$scope])->save();

    CharacterRole::updateOrCreate(
        ['character_id' => testCharacter()->character_id],
        ['roles' => ['Accountant']],
    );

    $job = new CorporationWalletJournalByDivisionJob(testCharacter()->corporation_id, 3);

    expect($job->getRefreshToken())->toBeInstanceOf(RefreshToken::class);
});

it('throws when no eligible token is found for corporation wallet journal', function () {
    $job = new CorporationWalletJournalByDivisionJob(99999999, 3);

    expect(fn () => $job->getRefreshToken())->toThrow(Exception::class);
});
