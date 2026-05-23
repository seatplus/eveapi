<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

beforeEach(fn () => Queue::fake());

it('sets from_id to latest transaction id minus one when latest transaction exists', function () {
    $character_id = testCharacter()->character_id;

    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $character_id,
        'transaction_id' => 100,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([]));

    $job = new CharacterWalletTransactionJob($character_id);
    $job->executeJob($esi);

    $property = (new ReflectionClass($job))->getProperty('from_id');

    expect($property->getValue($job))->toBe(99);
});

it('keeps from_id as PHP_INT_MAX when no latest transaction exists', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([]));

    $job = new CharacterWalletTransactionJob(testCharacter()->character_id);
    $job->executeJob($esi);

    $property = (new ReflectionClass($job))->getProperty('from_id');

    expect($property->getValue($job))->toBe(PHP_INT_MAX);
});

it('breaks when transaction_id is equal to the from_id', function () {
    $character_id = testCharacter()->character_id;

    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $character_id,
        'transaction_id' => 100,
    ]);

    $transactionData = [(object) [
        'transaction_id' => 100,
        'client_id' => 12345,
        'date' => '2021-01-01T00:00:00Z',
        'is_buy' => true,
        'is_personal' => true,
        'journal_ref_id' => 12345,
        'location_id' => 12345,
        'quantity' => 1,
        'type_id' => 12345,
        'unit_price' => 12345,
    ]];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($transactionData));

    $job = new CharacterWalletTransactionJob($character_id);
    $job->executeJob($esi);

    $property = (new ReflectionClass($job))->getProperty('from_id');

    expect($property->getValue($job))->not()->toBe(100);
});

it('returns early when result is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterWalletTransactionJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(WalletTransaction::count())->toBe(0);
});
