<?php


use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(TestCase::class);
uses(MockRetrieveEsiDataAction::class);

test('job creates d b entry', function () {
    $corporation_history = CorporationHistory::factory()->count(3)->make([
        'character_id' => $this->test_character->character_id,
        'corporation_id' => $this->test_character->corporation->corporation_id,
    ]);

    $this->mockRetrieveEsiDataAction($corporation_history->toArray());

    $job_container = new JobContainer(['refresh_token' => $this->test_character->refresh_token]);

    expect(CorporationHistory::all())->toHaveCount(0);

    CorporationHistoryJob::dispatchSync($job_container);

    expect(CorporationHistory::all())->toHaveCount(3);
    expect($this->test_character->corporation_history)->toHaveCount(3);
});
