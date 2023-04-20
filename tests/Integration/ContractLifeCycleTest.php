<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Contracts\Contract;

test('job dispatches nothing by default upon creation with factory', function () {
    Queue::fake();

    $contract = Contract::factory()->make([
        'for_corporation' => true,
    ]);

    expect($contract->start_location)->not()->toBeNull()
        ->and($contract->end_location)->not()->toBeNull()
        ->and($contract->issuer)->not()->toBeNull();

    mockRetrieveEsiDataAction([$contract->toArray()]);

    (new CharacterContractsJob($contract->issuer_id))->handle();

    Queue::assertNotPushed(ResolveLocationJob::class);
});

test('job dispatches location job with unknown location id', function () {
    Queue::fake();

    $contract = Contract::factory()->create([
        'assignee_id' => $this->test_character->character_id,
        'start_location_id' => 123456,
        'for_corporation' => true,
    ]);

    mockRetrieveEsiDataAction([$contract->toArray()]);

    (new CharacterContractsJob(testCharacter()->character_id))->handle();

    expect($contract->start_location)->toBeNull()
        ->and($contract->end_location)->not()->toBeNull()
        ->and($contract->issuer)->not()->toBeNull();

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});
