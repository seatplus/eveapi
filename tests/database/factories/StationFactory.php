<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;

$factory->define(Station::class, function (Faker $faker) {

    return [
        'station_id' => $faker->numberBetween(60000000,64000000),
        'name' => $faker->name,
        'owner_id'  => $faker->optional()->numberBetween(98000000,99000000),
        'system_id'  => factory(System::class),
        'type_id' => $faker->numberBetween(0,10000),
        'race_id'  => $faker->optional()->numberBetween(98000000,99000000),
        'reprocessing_efficiency' => $faker->randomNumber(),
        'reprocessing_stations_take' => $faker->randomNumber(),
        'max_dockable_ship_volume' => $faker->randomNumber(),
        'office_rental_cost' => $faker->randomDigit

    ];
});

$factory->state(Station::class, 'noSystem', function (Faker $faker) {
    return [
        'system_id'  => $faker->numberBetween(30000000,31000000),
    ];
});
