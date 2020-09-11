<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

$factory->define(Applications::class, function (Faker $faker) {

    return [
        'corporation_id' => factory(CorporationInfo::class),
        'applicationable_type' => CharacterInfo::class,
        'applicationable_id' => factory(CharacterInfo::class)
    ];
});
