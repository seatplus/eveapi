<?php

use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;

test('returns early if cached', function () {

    $response = mock(EsiResponse::class);
    $response->shouldReceive('isCachedLoad')->once()->andReturn(true);

    $job = mock(CharacterContractsJob::class)->makePartial();
    $job->shouldReceive('retrieve')->once()->andReturn($response);

    $job->executeJob();

    expect(true)->toBeTrue();
});

it('increments page', function () {

    Queue::fake();

    $contract = \Seatplus\Eveapi\Models\Contracts\Contract::factory()->count(2)->make();

    $response1 = new EsiResponse(json_encode($contract->toArray()), ['X-Pages' => 2], 'now', 200);
    $response2 = new EsiResponse('{}', ['X-Pages' => 2], 'now', 200);

    $job = mock(CharacterContractsJob::class)->makePartial();
    $job->character_id = 1;
    $job->shouldReceive('retrieve')->twice()->andReturns($response1, $response2);

    $job->executeJob();

    expect($job->getPage())->toEqual(2);
});

it('adds follow up jobs to batch if batching', function () {

        Queue::fake();

        $contract = \Seatplus\Eveapi\Models\Contracts\Contract::factory()->count(2)->make();

        $response = new EsiResponse(json_encode($contract->toArray()), [], 'now', 200);

        $job = mock(CharacterContractsJob::class)->makePartial();
        $job->character_id = 1;
        $job->shouldReceive('retrieve')->andReturn($response);

        $job->shouldReceive('batching')->once()->andReturnTrue();
        $job->shouldReceive('batch->add')->once();

        $job->executeJob();

        Queue::assertNothingPushed();
});
