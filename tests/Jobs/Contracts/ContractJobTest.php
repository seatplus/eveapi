<?php


namespace Seatplus\Eveapi\Tests\Jobs\Contracts;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Contracts\CharacterContractsJob;
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class ContractJobTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /** @test */
    public function jobIsBeingDispatched()
    {
        Queue::fake();

        // Assert that no jobs were pushed...
        Queue::assertNothingPushed();

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        CharacterContractsJob::dispatch($job_container)->onQueue('default');

        // Assert a job was pushed to a given queue...
        Queue::assertPushedOn('default', CharacterContractsJob::class);
    }

    /** @test */
    public function itRunsWithEmptyResponse()
    {
        $this->mockRetrieveEsiDataAction([]);

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        $job = new CharacterContractsJob($job_container);

        dispatch_now($job);
    }

    /** @test */
    public function itCreatesContractJob()
    {
        $mock_data = $this->buildMockEsiData();

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        $job = new CharacterContractsJob($job_container);

        $this->assertCount(0, $this->test_character->refresh()->contracts);

        Event::fakeFor(fn () => dispatch_now($job));

        $this->assertCount(5, Contract::all());
        $this->assertCount(5, $this->test_character->refresh()->contracts);
    }

    /** @test */
    public function itCreatesContractJobOtherWay()
    {
        $mock_data = $this->buildMockEsiData();

        $job_container = new JobContainer([
            'refresh_token' => $this->test_character->refresh_token,
        ]);

        Event::fakeFor(fn () => CharacterContractsJob::dispatchNow($job_container));

        $this->assertCount(5, Contract::all());
    }

    private function buildMockEsiData(int $count = 5)
    {
        $mock_data = Contract::factory()->count($count)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }
}
