<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;

it('does not execute job if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    $this->assertDatabaseMissing('wallet_journals', [
        'wallet_journable_id' => testCharacter()->character_id,
    ]);
});

it('handles contextable type', function ($context_id_type) {
    $data = [(object) [
        'context_id_type' => $context_id_type,
        'context_id' => 12345,
        'id' => 12345,
        'date' => now(),
        'description' => 'test',
        'ref_type' => 'test',
        'amount' => 100.0,
        'balance' => 200.0,
        'first_party_id' => null,
        'second_party_id' => null,
        'reason' => null,
        'tax' => null,
        'tax_receiver_id' => null,
    ]];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($data));

    $job = new CharacterWalletJournalJob(testCharacter()->character_id);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

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
