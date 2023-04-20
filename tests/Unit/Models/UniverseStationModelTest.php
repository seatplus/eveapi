<?php

use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;

test('character has corporation relation test', function () {
    $station = Station::factory()->create();

    expect($station->system)->toBeInstanceOf(System::class);
});
