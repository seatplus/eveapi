<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Wallet\WalletJournalBase;

it('does not execute job if response is cached', function () {
    $job = mock(WalletJournalBase::class,function (\Mockery\MockInterface $mock) {
        $mock->shouldReceive('getPathValues')->once()->andReturn([
            'character_id' => 12345,
        ]);

        $response = mock(EsiResponse::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('isCachedLoad')->once()->andReturnTrue();
        });

        $mock->shouldReceive('retrieve')->once()->andReturn($response);

    })->makePartial();

    $job->executeJob();

    $this->assertDatabaseMissing('wallet_journals', [
        'wallet_journable_id' => 12345,
    ]);
});

it('handles multiple pages correctly', function () {

    $job = mock(WalletJournalBase::class,function (\Mockery\MockInterface $mock) {
        $mock->shouldReceive('getPathValues')->once()->andReturn([
            'character_id' => 12345,
        ]);

        $response = mock(EsiResponse::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('isCachedLoad')->andReturnFalse();
            $mock->pages = 2;
            $mock->shouldReceive('getIterator')->andReturn(new ArrayIterator([]));
        });

        $mock->shouldReceive('retrieve')->twice()->andReturn($response);

        $mock->shouldReceive('getPage')->andReturn(1, 1, 2, 2);
        $mock->shouldReceive('incrementPage')->once();

    })->makePartial();

    $job->executeJob();
});

it('handles contextable type', function ($context_id_type) {
    $job = mock(WalletJournalBase::class,function (\Mockery\MockInterface $mock) use ($context_id_type) {
        $mock->shouldReceive('getPathValues')->once()->andReturn([
            'corporation_id' => 12345,
        ]);

        $response = mock(EsiResponse::class, function (\Mockery\MockInterface $mock) use ($context_id_type) {
            $mock->shouldReceive('isCachedLoad')->andReturnFalse();
            $mock->pages = 1;
            $mock->shouldReceive('getIterator')->andReturn(new ArrayIterator([
                (object)[
                    'context_id_type' => $context_id_type,
                    'id' => 12345,
                    'date' => now(),
                    'description' => 'test',
                    'ref_type' => 'test',
                ]
            ]));
        });

        $mock->shouldReceive('retrieve')->once()->andReturn($response);

    })->makePartial();

    $job->executeJob();


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
    null
]);
