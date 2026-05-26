<?php

use Mockery\MockInterface;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;

beforeEach(function () {
    Queue::fake();
});

it('returns correct unique id', function () {
    $job = new CorporationWalletJournalJob(12345);

    expect($job->uniqueId())->toBe('Corporation wallet journal dispatcher for corporation_id 12345 ');
});

it('returns correct tags', function () {
    $job = new CorporationWalletJournalJob(12345);

    expect($job->tags())->toBe([
        'corporation',
        'corporation_id: 12345',
        'wallet',
        'journals',
    ]);
});

describe('handle batching', function () {

    beforeEach(function () {
        Balance::factory()->withDivision()->create([
            'balanceable_id' => testCharacter()->corporation_id,
            'balanceable_type' => CorporationInfo::class,
        ]);
    });

    test('cancelled', function () {

        $job = mock(CorporationWalletJournalJob::class, [testCharacter()->corporation_id], function (MockInterface $mock) {
            $mock->shouldReceive('batching')->andReturnTrue();
            $mock->shouldReceive('batch->cancelled')->once()->andReturnTrue();
        })->makePartial();

        $job->handle();
    });

    test('adding to batch', function () {

        $job = mock(CorporationWalletJournalJob::class, [testCharacter()->corporation_id], function (MockInterface $mock) {
            $mock->shouldReceive('batching')->andReturnTrue();
            $mock->shouldReceive('batch->cancelled')->once()->andReturnFalse();
            $mock->shouldReceive('batch->add')->once();
        })->makePartial();

        $job->handle();
    });

});
