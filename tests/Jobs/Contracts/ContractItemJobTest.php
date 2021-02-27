<?php


namespace Seatplus\Eveapi\Tests\Jobs\Contracts;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Jobs\Contracts\ContractItemsJob;
use Seatplus\Eveapi\Jobs\Seatplus\ResolveUniverseTypesByTypeIdJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
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

        $mock_data = $this->buildMockEsiData(1);

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token
        ]);

        ContractItemsJob::dispatch($mock_data->first()->contract_id, $job_container, 'character');

        // Assert no
        Queue::assertNotPushed(ResolveUniverseTypesByTypeIdJob::class);
    }

    /** @test */
    public function itDispatchesResolveUniverseTypeJobIfTypeIsUnknown()
    {

        Queue::fake();

        $mock_data = ContractItem::factory()->withoutType()->count(5)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token
        ]);

        $job = new ContractItemsJob($mock_data->first()->contract_id, $job_container);

        $job->handle();

        $this->assertCount(5, ContractItem::all());

        Queue::assertPushed(ResolveUniverseTypesByTypeIdJob::class);

    }

    private function buildMockEsiData(int $count = 5)
    {

        $mock_data = ContractItem::factory()->count($count)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

}
