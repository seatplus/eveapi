<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Killmails\KillmailJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseSystemBySystemIdJob;
use Seatplus\Eveapi\Jobs\Universe\ResolveUniverseTypeByIdJob;
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

    [$job, $batch] = new KillmailJob($killmail->killmail_id, $killmail->killmail_hash)->withFakeBatch();

    $job->executeJob($esi);

    // The unknown solar system and the unknown ship type are resolved through the batch,
    // not dispatched on their own.
    expect($batch->added)->toHaveCount(2)
        ->and($batch->added[0])->toBeInstanceOf(ResolveUniverseSystemBySystemIdJob::class)
        ->and($batch->added[1])->toBeInstanceOf(ResolveUniverseTypeByIdJob::class);

    Queue::assertNothingPushed();
});
