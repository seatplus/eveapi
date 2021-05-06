<?php


namespace Seatplus\Eveapi\Tests\Integration;


use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

class ContractLifeCycleTest extends TestCase
{
    /** @test */
    public function ObserverDispatchesNothingByDefaultUponCreationWithFactory()
    {
        $fake  = Queue::fake();

        $contract = Contract::factory()->create(['for_corporation' => true]);

        $this->assertNotNull($contract->start_location);
        $this->assertNotNull($contract->end_location);
        $this->assertNotNull($contract->issuer);

        Queue::assertNothingPushed();
    }

    /** @test */
    public function ObserverDispatchesLocationJobWithUnknownLocationId()
    {
        $fake  = Queue::fake();

        $contract = Contract::factory()->create([
            'assignee_id' => $this->test_character->character_id,
            'start_location_id' => 123456,
            'for_corporation' => true
        ]);

        $this->assertNull($contract->start_location);
        $this->assertNotNull($contract->end_location);
        $this->assertNotNull($contract->issuer);

        Queue::assertPushedOn('high', ResolveLocationJob::class);
    }
}
