<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractItemsJob;

it('has tags', function () {
    $job = new CharacterContractItemsJob(character_id: 1, contract_id: 42);

    expect($job->tags())->toBeArray();
});

it('stops executing when batch is cancelled', function () {
    $esi = Mockery::mock(EsiClient::class);

    $job = mock(CharacterContractItemsJob::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->contract_id = 1;
    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob($esi);

    expect(true)->toBeTrue();
});

it('does stop executing if response is cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterContractItemsJob(character_id: 1, contract_id: 1);
    (new ReflectionMethod($job, 'executeJob'))->invoke($job, $esi);

    expect(true)->toBeTrue();
});
