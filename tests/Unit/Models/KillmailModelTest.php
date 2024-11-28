<?php

use Seatplus\Eveapi\Models\Killmails\Killmail;
use Seatplus\Eveapi\Models\Killmails\KillmailAttacker;
use Seatplus\Eveapi\Models\Killmails\KillmailItem;
use Seatplus\Eveapi\Models\Universe\System;

it('has one system', function () {
    $system = System::factory()->create();
    $killmail = Killmail::factory()->create(['solar_system_id' => $system->system_id]);

    expect($killmail->system->is($system))->toBeTrue();
});

it('deletes related attackers and items', function () {
    $killmail = Killmail::factory()->create();


    KillmailAttacker::query()->create([
        'killmail_id' => $killmail->killmail_id,
        'damage_done' => 123,
    ]);

    KillmailItem::query()->create([
        'location_id' => $killmail->killmail_id,
        'location_flag' => 'everything_else',
        'quantity' => 1,
        'type_id' => 123,
    ]);

    $killmail->delete();

    expect(KillmailAttacker::where('killmail_id', $killmail->killmail_id)->exists())->toBeFalse()
        ->and(KillmailItem::where('location_id', $killmail->killmail_id)->exists())->toBeFalse();
});
