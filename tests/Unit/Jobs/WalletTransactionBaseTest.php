<?php

use Mockery\MockInterface;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Wallet\WalletTransactionBase;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

beforeEach(fn () => Queue::fake());

it('sets from_id to latest transaction id minus one when latest transaction exists', function () {
    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => 12345,
        'transaction_id' => 100,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    $result = makeEsiResult([]);

    $job = mock(WalletTransactionBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('transactionableId')->andReturn(12345);
        $mock->shouldReceive('transactionableType')->andReturn(CharacterInfo::class);
        $mock->shouldReceive('fetchTransactions')->andReturn($result);
        $mock->shouldReceive('getRefreshToken')->andReturn(testCharacter()->refresh_token);
    })->makePartial();

    $job->executeJob($esi);

    $reflection = new ReflectionClass($job);
    $property = $reflection->getProperty('from_id');
    $property->setAccessible(true);

    expect($property->getValue($job))->toBe(99);
});

it('keeps from_id as PHP_INT_MAX when no latest transaction exists', function () {
    $esi = Mockery::mock(EsiClient::class);
    $result = makeEsiResult([]);

    $job = mock(WalletTransactionBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('transactionableId')->andReturn(12345);
        $mock->shouldReceive('transactionableType')->andReturn(CharacterInfo::class);
        $mock->shouldReceive('fetchTransactions')->andReturn($result);
        $mock->shouldReceive('getRefreshToken')->andReturn(testCharacter()->refresh_token);
    })->makePartial();

    $job->executeJob($esi);

    $reflection = new ReflectionClass($job);
    $property = $reflection->getProperty('from_id');
    $property->setAccessible(true);

    expect($property->getValue($job))->toBe(PHP_INT_MAX);
});

it('breaks when transaction_id is equal to the from_id', function () {
    WalletTransaction::factory()->create([
        'wallet_transactionable_id' => 12345,
        'transaction_id' => 100,
    ]);

    $esi = Mockery::mock(EsiClient::class);
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
    $result = makeEsiResult($transactionData);

    $job = mock(WalletTransactionBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('transactionableId')->andReturn(12345);
        $mock->shouldReceive('transactionableType')->andReturn(CharacterInfo::class);
        $mock->shouldReceive('fetchTransactions')->andReturn($result);
        $mock->shouldReceive('getRefreshToken')->andReturn(testCharacter()->refresh_token);
    })->makePartial();

    $job->executeJob($esi);

    $reflection = new ReflectionClass($job);
    $property = $reflection->getProperty('from_id');
    $property->setAccessible(true);

    expect($property->getValue($job))->not()->toBe(100);
});

it('returns early when result is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $result = makeEsiResult([], isCachedLoad: true);

    $job = mock(WalletTransactionBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('transactionableId')->andReturn(12345);
        $mock->shouldReceive('transactionableType')->andReturn(CharacterInfo::class);
        $mock->shouldReceive('fetchTransactions')->andReturn($result);
        $mock->shouldReceive('getRefreshToken')->andReturn(testCharacter()->refresh_token);
    })->makePartial();

    $job->executeJob($esi);

    expect(WalletTransaction::count())->toBe(0);
});
