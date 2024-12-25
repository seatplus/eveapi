<?php

it('has tags', function () {

    $job = mock(\Seatplus\Eveapi\Jobs\Contracts\ContractItemsJob::class)->makePartial();
    $job->contract_id = 1;

    expect($job->tags())->toBeArray();
});

it('stops executing when batch is cancelled', function () {

    $job = mock(\Seatplus\Eveapi\Jobs\Contracts\ContractItemsJob::class)->makePartial();
    $job->contract_id = 1;

    $job->shouldReceive('batching')->once()->andReturn(true);
    $job->shouldReceive('batch->cancelled')->once()->andReturn(true);

    $job->executeJob();

    expect(true)->toBeTrue();
});

it('does stop executing if response is cached', function () {

    $response = mock(\Seatplus\EsiClient\DataTransferObjects\EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->andReturn(true);

    $job = mock(\Seatplus\Eveapi\Jobs\Contracts\ContractItemsJob::class)->makePartial();
    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

    expect(true)->toBeTrue();
});
