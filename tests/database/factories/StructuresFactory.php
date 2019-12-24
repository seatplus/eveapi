<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Structure;

$factory->define(Structure::class, function (Faker $faker) {

    return [
        'structure_id' => $faker->numberBetween(0,10000),
        'name' => $faker->name,
        'owner_id'  => $faker->numberBetween(98000000,99000000),
        'solar_system_id'  => $faker->numberBetween(30000000,31000000),
        'type_id' => $faker->optional()->numberBetween(0,10000),
    ];
});
