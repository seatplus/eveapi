<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    AllianceInfoJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', AllianceInfoJob::class);
});

test('retrieve test', function () {
    $mock_data = AllianceInfo::factory()->make(['alliance_id' => $this->test_character->character_id]);

    $dto = (object) array_merge(['isCachedLoad' => false], $mock_data->toArray());
    mockEsiClient('alliance->getAlliancesAllianceId', $dto);

    runJob(new AllianceInfoJob($this->test_character->character_id));

    $this->assertDatabaseHas('alliance_infos', [
        'name' => $mock_data->name,
    ]);
});
