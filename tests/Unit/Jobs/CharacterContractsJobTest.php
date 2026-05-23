<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;

test('returns early if cached', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new CharacterContractsJob(1);
    $method = new ReflectionMethod($job, 'executeJob');
    $method->invoke($job, $esi);

    expect(true)->toBeTrue();
});

it('adds follow up jobs to batch if batching', function () {
    Queue::fake();

    $contract = Contract::factory()->count(2)->make();

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(
        array_map(fn ($c) => (object) $c, $contract->toArray())
    ));

    $job = mock(CharacterContractsJob::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->character_id = 1;
    $job->shouldReceive('batching')->once()->andReturnTrue();
    $job->shouldReceive('batch->add')->once();

    $job->executeJob($esi);

    Queue::assertNothingPushed();
});
