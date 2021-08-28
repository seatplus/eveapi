<?php


namespace Seatplus\Eveapi\Tests\Integration;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterWalletJournalLifecycleTest extends TestCase
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

        $job = new CharacterWalletJournalJob($this->job_container);

        dispatch_now($job);

        $this->assertWalletJournal($mock_data, $this->test_character->character_id);
    }

    private function buildMockEsiData()
    {
        $mock_data = WalletJournal::factory()->count(5)->make();

        $this->mockRetrieveEsiDataAction($mock_data->toArray());

        return $mock_data;
    }

    private function assertWalletJournal(Collection $mock_data, int $wallet_journable_id)
    {
        foreach ($mock_data as $data) {
            //Assert that character asset created
            $this->assertDatabaseHas('wallet_journals', [
                'wallet_journable_id' => $wallet_journable_id,
                'id' => $data->id,
            ]);
        }
    }
}
