<?php


use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Wallet\CharacterWalletJournalJob;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

beforeEach(function () {
    // Prevent any auto dispatching of jobs
    Queue::fake();

    $this->job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);
});

test('run wallet journal job', function () {
    $mock_data = buildMockEsiData();

    $job = new CharacterWalletJournalJob($this->job_container);

    dispatch_now($job);

    assertWalletJournal($mock_data, $this->test_character->character_id);
});

// Helpers
function buildMockEsiData()
{
    $mock_data = WalletJournal::factory()->count(5)->make();

    $this->mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}

function assertWalletJournal(Collection $mock_data, int $wallet_journable_id)
{
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('wallet_journals', [
            'wallet_journable_id' => $wallet_journable_id,
            'id' => $data->id,
        ]);
    }
}
