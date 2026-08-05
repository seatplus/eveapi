<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Responses\CharactersCharacterIdWalletTransactionsGetItem;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletTransactionJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

beforeEach(fn () => Queue::fake());

it('only resolves types for its own transactions, not another owner', function () {
    $characterId = testCharacter()->character_id;

    // An unresolved-type transaction belonging to a DIFFERENT character. Before the scope fix
    // the follow-up scan was global and would resolve this; now it must be ignored.
    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => 999999,
        'wallet_transactionable_type' => CharacterInfo::class,
        'type_id' => 8888,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([])); // this character has no transactions

    new CharacterWalletTransactionJob($characterId)->executeJob($esi);

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('sets from_id to latest transaction id minus one when latest transaction exists', function () {
    $characterId = testCharacter()->character_id;

    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $characterId,
        'transaction_id' => 100,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([]));

    $job = new CharacterWalletTransactionJob($characterId);
    $job->executeJob($esi);

    $property = new ReflectionClass($job)->getProperty('fromId');

    expect($property->getValue($job))->toBe(99);
});

it('keeps from_id as PHP_INT_MAX when no latest transaction exists', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([]));

    $job = new CharacterWalletTransactionJob(testCharacter()->character_id);
    $job->executeJob($esi);

    $property = new ReflectionClass($job)->getProperty('fromId');

    expect($property->getValue($job))->toBe(PHP_INT_MAX);
});

it('breaks when transaction_id is equal to the from_id', function () {
    $characterId = testCharacter()->character_id;

    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => $characterId,
        'transaction_id' => 100,
    ]);

    $transactionData = [CharactersCharacterIdWalletTransactionsGetItem::from((object) [
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
    ])];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult($transactionData));

    $job = new CharacterWalletTransactionJob($characterId);
    $job->executeJob($esi);

    $property = new ReflectionClass($job)->getProperty('fromId');

    expect($property->getValue($job))->not()->toBe(100);
});

it('returns early when result is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterWalletTransactionJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(WalletTransaction::count())->toBe(0);
});

it('runs its write outside the whole-job transaction', function () {
    $job = new CharacterWalletTransactionJob(12345);

    expect((fn () => $this->wrapExecuteJobInTransaction())->call($job))->toBeFalse();
});
