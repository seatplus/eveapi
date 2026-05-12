<?php

use Illuminate\Support\Facades\Event;
use Seatplus\EsiClient\DataTransferObjects\EsiResponse;
use Seatplus\Eveapi\Jobs\Killmails\KillmailJob;
use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Universe\System;

it('returns early if cache is hit', function () {

    $job = mock(KillmailJob::class)->makePartial();

    $job->shouldReceive('retrieve')
        ->andReturn(
            mock(EsiResponse::class)
                ->shouldReceive('isCachedLoad')
                ->andReturn(true)
                ->getMock()
        );

    $job->executeJob();

    expect(Killmail::count())->toEqual(0);
});

it('does not further execute if killmail is complete', function () {

    Illuminate\Support\Facades\Queue::fake();

    $killmail = Event::fakeFor(fn () => Killmail::factory()->create([
        'complete' => true,
        'solar_system_id' => 12345,
    ]));

    $data = [
        'solar_system_id' => $killmail->solar_system_id,
        'victim' => [
            'character_id' => $killmail->victim_character_id,
            'corporation_id' => $killmail->victim_corporation_id,
            'alliance_id' => $killmail->victim_alliance_id,
            'ship_type_id' => $killmail->ship_type_id,
            'faction_id' => $killmail->victim_faction_id,
            'damage_taken' => $killmail->damage_taken,
        ],
    ];

    $response = new EsiResponse(json_encode($data), [], 'now', 200);

    $job = mock(KillmailJob::class)->makePartial();
    $job->killmail_id = $killmail->killmail_id;
    $job->killmail_hash = $killmail->killmail_hash;

    $job->shouldReceive('retrieve')->andReturn($response);

    $job->executeJob();

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

    $data = [
        'solar_system_id' => $killmail->solar_system_id,
        'victim' => [
            'character_id' => $killmail->victim_character_id,
            'corporation_id' => $killmail->victim_corporation_id,
            'alliance_id' => $killmail->victim_alliance_id,
            'ship_type_id' => $killmail->ship_type_id,
            'faction_id' => $killmail->victim_faction_id,
            'damage_taken' => $killmail->damage_taken,
            'items' => [],
        ],
        'attackers' => [],
    ];

    $response = new EsiResponse(json_encode($data), [], 'now', 200);

    $job = mock(KillmailJob::class)->makePartial();
    $job->killmail_id = $killmail->killmail_id;
    $job->killmail_hash = $killmail->killmail_hash;

    $job->shouldReceive('retrieve')->andReturn($response);
    $job->shouldReceive('batching')->twice()->andReturn(true);
    $job->shouldReceive('batch->add')->twice();

    $job->executeJob();

    Queue::assertNothingPushed();
});
