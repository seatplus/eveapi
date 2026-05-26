<?php

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Character\CorporationHistoryJob;
use Seatplus\Eveapi\Models\Character\CorporationHistory;

test('job creates db entry', function () {
    $corporation_history = CorporationHistory::factory()->count(3)->make([
        'character_id' => $this->test_character->character_id,
        'corporation_id' => $this->test_character->corporation->corporation_id,
    ]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult(array_map(fn ($h) => (object) $h, $corporation_history->toArray())));

    expect(CorporationHistory::all())->toHaveCount(0);

    $job = new CorporationHistoryJob(testCharacter()->character_id);
    $job->executeJob($esi);

    expect(CorporationHistory::all())->toHaveCount(3)
        ->and($this->test_character->corporation_history)->toHaveCount(3);
});
