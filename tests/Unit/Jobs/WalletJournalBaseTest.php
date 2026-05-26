<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdWalletJournalGetItem;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

it('does not execute job if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    $job->executeJob($esi);

    $this->assertDatabaseMissing('wallet_journals', [
        'wallet_journable_id' => testCharacter()->character_id,
    ]);
});

it('handles multiple pages correctly', function () {
    $entry = fn (int $id) => CharactersCharacterIdWalletJournalGetItem::from((object) [
        'id' => $id,
        'date' => now()->toIso8601String(),
        'description' => 'test',
        'ref_type' => 'test',
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

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(WalletJournal::count())->toBe(2);
});

it('handles contextable type', function ($context_id_type) {
    $data = [CharactersCharacterIdWalletJournalGetItem::from((object) [
        'id' => 12345,
        'date' => now()->toIso8601String(),
        'description' => 'test',
        'ref_type' => 'test',
        'amount' => 100.0,
        'balance' => 200.0,
        'context_id' => 12345,
        'context_id_type' => $context_id_type,
    ])];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($data));

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    $job->executeJob($esi);

    $this->assertDatabaseHas('wallet_journals', [
        'id' => 12345,
        'wallet_journable_id' => testCharacter()->character_id,
    ]);
})->with([
    'structure_id',
    'station_id',
    'market_transaction_id',
    'character_id',
    'corporation_id',
    'alliance_id',
    'eve_system',
    'industry_job_id',
    'contract_id',
    'planet_id',
    'system_id',
    'type_id',
]);
