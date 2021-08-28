<?php


namespace Seatplus\Eveapi\Tests\Jobs\Contracts;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contracts\ContractItemsJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ContractItemJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /** @test */
    public function jobIsBeingDispatched()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $mock_data = ContractItem::factory()->count(1)->make();

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        $this->assertRetrieveEsiDataIsNotCalled();

        ContractItemsJob::dispatch($mock_data->first()->contract_id, $job_container, 'character');

        // Assert no
        Queue::assertNotPushed(ResolveUniverseTypeByIdJob::class);
    }

    /** @test */
    public function itDispatchesResolveUniverseTypeJobIfTypeIsUnknown()
    {
        Queue::fake();

        $mock_data = ContractItem::factory()->withoutType()->count(5)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        $job = new ContractItemsJob($mock_data->first()->contract_id, $job_container);

        $job->handle();

        $this->assertCount(5, ContractItem::all());

        Queue::assertPushed(ResolveUniverseTypeByIdJob::class);
    }

    private function buildMockEsiData(int $count = 5)
    {
        $mock_data = ContractItem::factory()->count($count)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }
}
