<?php


namespace Seatplus\Eveapi\Tests\Integration;

use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CorporationHistoryTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /** @test */
    public function jobCreatesDBEntry()
    {
        $corporation_history = CorporationHistory::factory()->count(3)->make([
            'character_id' => $this->test_character->character_id,
            'corporation_id' => $this->test_character->corporation->corporation_id,
        ]);

        $this->mockRetrieveEsiDataAction($corporation_history->toArray());

        $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

        $this->assertCount(0, CorporationHistory::all());

        CorporationHistoryJob::dispatchSync($job_container);

        $this->assertCount(3, CorporationHistory::all());
        $this->assertCount(3, $this->test_character->corporation_history);
    }
}
