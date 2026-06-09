<?php

use Illuminate\Support\Facades\Queue;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

test('if job is queued', function () {
    Queue::fake();

    Queue::assertNothingPushed();

    AllianceInfoJob::dispatch($this->test_character->character_id)->onQueue('default');

    Queue::assertPushedOn('default', AllianceInfoJob::class);
});

test('retrieve test', function () {
    $mockData = AllianceInfo::factory()->make(['alliance_id' => 12345]);

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult((object) $mockData->toArray()));

    $job = new AllianceInfoJob(12345);
    $job->executeJob($esi);

    expect(AllianceInfo::where('name', $mockData->name)->exists())->toBeTrue();
});
