<?php


use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Tests\TestCase;

uses(TestCase::class);

test('character has corporation relation test', function () {
    $station = Station::factory()->create();

    $this->assertInstanceOf(System::class, $station->system);
});
