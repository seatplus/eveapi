<?php

use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\System;
use Faker\Generator as Faker;

$factory->define(System::class, function (Faker $faker) {

    return [
        'system_id' => $faker->numberBetween(30000000,32000000),
        'name' => $faker->name,
        'constellation_id'  => factory(Constellation::class),
        'security_class'  => $faker->optional()->word,
        'security_status' => $faker->randomFloat($nbMaxDecimals = 1, $min = -1, $max = 1),
    ];
});

$factory->state(System::class, 'noConstellation', function (Faker $faker) {
    return [
        'constellation_id'  => $faker->numberBetween(20000000,22000000),
    ];
});
