<?php

use Illuminate\Support\Facades\Event;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;

beforeEach(function () {
});

it('has corporation releationship', function () {
    $balance = Event::fakeFor(fn () => Balance::factory()->withDivision()->create([
        'balanceable_id' => $this->test_character->corporation->corporation_id,
        'balanceable_type' => CorporationInfo::class,
    ]));

    $this->assertNotNull($balance->refresh()->balanceable);
});
