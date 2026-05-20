<?php

use Mockery\MockInterface;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contracts\ContractItemsBase;

it('has tags', function () {
    $job = mock(ContractItemsBase::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->contract_id = 1;

    expect($job->tags())->toBeArray();
});

it('stops executing when batch is cancelled', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = mock(ContractItemsBase::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->contract_id = 1;

    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob($esi);

    expect(true)->toBeTrue();
});

it('does stop executing if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    $result = makeEsiResult([], isCachedLoad: true);

    $job = mock(ContractItemsBase::class, function (MockInterface $mock) use ($result) {
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('fetchItems')->once()->andReturn($result);
    })->makePartial();
    $job->contract_id = 1;

    $job->executeJob($esi);

    expect(true)->toBeTrue();
});
