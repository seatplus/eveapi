<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Applications;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

$factory->define(Applications::class, function (Faker $faker) {

    return [
        'character_id' => factory(CharacterInfo::class),
        'corporation_id' => factory(CorporationInfo::class),
    ];
});
