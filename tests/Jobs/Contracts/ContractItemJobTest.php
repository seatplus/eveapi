<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractItemsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Contracts\ContractItem;

test('job is being dispatched', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    $mock_data = ContractItem::factory()->count(1)->make();

    CharacterContractItemsJob::dispatch(testCharacter()->character_id, $mock_data->first()->contract_id);

    Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
});

it('dispatches resolve universe type job if type is unknown', function () {
    Queue::fake();

    $mock_data = ContractItem::factory()->withoutType()->count(5)->make();

    $contract = Event::fakeFor(fn () => Contract::factory()->create([
        'contract_id' => $mock_data->first()->contract_id,
    ]));

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($i) => (object) $i, $mock_data->toArray())));

    $job = new CharacterContractItemsJob(testCharacter()->character_id, $contract->contract_id);
    $job->executeJob($esi);

    expect(ContractItem::all())->toHaveCount(5);

    Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
});
