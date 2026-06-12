<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Killmails\KillmailJob;
use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Universe\System;

it('returns early if cache is hit', function () {
    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, makeEsiResult([], isCachedLoad: true));

    $job = new KillmailJob(12345, 'abc123');
    $job->executeJob($esi);

    expect(Killmail::count())->toEqual(0);
});

it('does not further execute if killmail is complete', function () {
    Illuminate\Support\Facades\Queue::fake();

    $killmail = Event::fakeFor(fn () => Killmail::factory()->create([
        'complete' => true,
        'solar_system_id' => 12345,
    ]));

    $data = (object) [
        'isCachedLoad' => false,
        'solar_system_id' => $killmail->solar_system_id,
        'victim' => (object) [
            'character_id' => $killmail->victim_character_id,
            'corporation_id' => $killmail->victim_corporation_id,
            'alliance_id' => $killmail->victim_alliance_id,
            'ship_type_id' => $killmail->ship_type_id,
            'faction_id' => $killmail->victim_faction_id,
            'damage_taken' => $killmail->damage_taken,
        ],
        'attackers' => [],
        'killmail_id' => $killmail->killmail_id,
        'killmail_time' => now()->toIso8601String(),
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    $job = new KillmailJob($killmail->killmail_id, $killmail->killmail_hash);
    $job->executeJob($esi);

    Queue::assertNothingPushed();
    expect(System::count())->toEqual(0);
});

it('adds to batch', function () {
    Queue::fake();

    $killmail = Event::fakeFor(fn () => Killmail::factory()->make([
        'complete' => false,
        'ship_type_id' => 12345,
        'solar_system_id' => 12345,
        'damage_taken' => 999,
    ]));

    $data = (object) [
        'isCachedLoad' => false,
        'solar_system_id' => $killmail->solar_system_id,
        'victim' => (object) [
            'character_id' => $killmail->victim_character_id,
            'corporation_id' => $killmail->victim_corporation_id,
            'alliance_id' => $killmail->victim_alliance_id,
            'ship_type_id' => $killmail->ship_type_id,
            'faction_id' => $killmail->victim_faction_id,
            'damage_taken' => $killmail->damage_taken,
            'items' => [],
        ],
        'attackers' => [],
        'killmail_id' => 99999,
        'killmail_time' => now()->toIso8601String(),
    ];

    $esi = Mockery::mock(EsiClient::class);
    mockEsiTransport($esi, $data);

    $job = mock(KillmailJob::class)->shouldAllowMockingProtectedMethods()->makePartial();
    $job->killmailId = $killmail->killmail_id;
    $job->killmailHash = $killmail->killmail_hash;

    $job->shouldReceive('batching')->twice()->andReturn(true);
    $job->shouldReceive('batch->add')->twice();

    $job->executeJob($esi);

    Queue::assertNothingPushed();
});
