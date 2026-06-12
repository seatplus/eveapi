<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractItemsJob;
use Seatplus\Eveapi\Models\Contracts\ContractItem;

it('has tags', function () {
    $job = new CharacterContractItemsJob(characterId: 1, contractId: 42);

    expect($job->tags())->toBeArray();
});

it('stops executing when batch is cancelled', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = mock(CharacterContractItemsJob::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->contractId = 1;
    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob($esi);
});

it('does stop executing if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterContractItemsJob(characterId: 1, contractId: 1);
    $job->executeJob($esi);

    expect(ContractItem::count())->toBe(0);
});
