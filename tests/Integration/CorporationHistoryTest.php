<?php


use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

uses(MockRetrieveEsiDataAction::class);

test('job creates db entry', function () {
    $corporation_history = CorporationHistory::factory()->count(3)->make([
        'character_id' => $this->test_character->character_id,
        'corporation_id' => $this->test_character->corporation->corporation_id,
    ]);

    mockRetrieveEsiDataAction($corporation_history->toArray());

    expect(CorporationHistory::all())->toHaveCount(0);

    CorporationHistoryJob::dispatchSync(testCharacter()->character_id);

    expect(CorporationHistory::all())->toHaveCount(3)
        ->and($this->test_character->corporation_history)->toHaveCount(3);
});
