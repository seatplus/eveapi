<?php

use Mockery\MockInterface;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\WalletJournalBase;

it('does not execute job if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $result = makeEsiResult([], isCachedLoad: true);

    $job = mock(WalletJournalBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('fetchPage')->once()->andReturn($result);
        $mock->shouldReceive('walletableId')->andReturn(12345);
        $mock->shouldReceive('walletableType')->andReturn('SomeClass');
    })->makePartial();

    $job->executeJob($esi);

    $this->assertDatabaseMissing('wallet_journals', [
        'wallet_journable_id' => 12345,
    ]);
});

it('handles contextable type', function ($context_id_type) {
    $esi = Mockery::mock(EsiClient::class);
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
    $result = makeEsiResult($data);

    $job = mock(WalletJournalBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('fetchPage')->once()->andReturn($result);
        $mock->shouldReceive('walletableId')->andReturn(12345);
        $mock->shouldReceive('walletableType')->andReturn('SomeClass');
    })->makePartial();

    $job->executeJob($esi);

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
