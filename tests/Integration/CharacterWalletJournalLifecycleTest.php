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
    $mock_data = buildWalletJournalMockEsiData();

    $job = new CharacterWalletJournalJob($this->job_container);

    dispatch_now($job);

    //assertWalletJournal($mock_data, $this->test_character->character_id);
    foreach ($mock_data as $data) {
        //Assert that character asset created
        $this->assertDatabaseHas('wallet_journals', [
            'wallet_journable_id' => $this->test_character->character_id,
            'id' => $data->id,
        ]);
    }
});

// Helpers
function buildWalletJournalMockEsiData()
{
    $mock_data = WalletJournal::factory()->count(5)->make();

    mockRetrieveEsiDataAction($mock_data->toArray());

    return $mock_data;
}
