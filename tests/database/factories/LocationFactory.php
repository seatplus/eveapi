<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Location;

$factory->define(Location::class, function (Faker $faker) {

    return [
        'location_id' => $faker->numberBetween(0,10000)
    ];
});
