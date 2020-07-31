<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationMemberTracking;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;

$factory->define(CorporationMemberTracking::class, function (Faker $faker) {

    return [
        'corporation_id'  => factory(CorporationInfo::class),
        'character_id' => factory(CharacterInfo::class),
        'location_id'  => $faker->numberBetween(0,10000),
    ];
});
