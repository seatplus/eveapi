<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Name;

$factory->define(Name::class, function (Faker $faker) {

    return [
        'id' => $faker->numberBetween(0,10000),
        'category'  => $faker->company,
        'name'  => $faker->name
    ];
});
