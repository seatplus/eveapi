<?php


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Universe\Location;

test('observer dispatches nothing by default upon creation with factory', function () {
    $fake = Queue::fake();

    $contract = Contract::factory()->create([
        'for_corporation' => true,
    ]);

    $this->assertNotNull($contract->start_location);
    $this->assertNotNull($contract->end_location);
    $this->assertNotNull($contract->issuer);

    Queue::assertNotPushed(ResolveLocationJob::class);
});

test('observer dispatches location job with unknown location id', function () {
    $fake = Queue::fake();

    $contract = Contract::factory()->create([
        'assignee_id' => $this->test_character->character_id,
        'start_location_id' => 123456,
        'for_corporation' => true,
    ]);

    expect($contract->start_location)->toBeNull();
    $this->assertNotNull($contract->end_location);
    $this->assertNotNull($contract->issuer);

    Queue::assertPushedOn('high', ResolveLocationJob::class);
});
