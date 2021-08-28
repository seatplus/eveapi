<?php


namespace Seatplus\Eveapi\Tests\Integration;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterBalanceLifecycleTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    public JobContainer $job_container;

    public function setUp(): void
    {
        parent::setUp();

        // Prevent any auto dispatching of jobs
        Queue::fake();

        $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
    }

    /** @test */
    public function runWalletJournalJob()
    {
        $mock_data = $this->buildMockEsiData();

        $this->assertCount(0, Balance::all());

        $job = new CharacterBalanceJob($this->job_container);

        $job->handle();

        $this->assertCount(1, Balance::all());
    }

    private function buildMockEsiData()
    {
        $mock_data = Balance::factory()->make();

        $this->mockRetrieveEsiDataAction(['scalar' => $mock_data->balance]);

        return $mock_data;
    }
}
