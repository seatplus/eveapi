<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Names;

$factory->define(Names::class, function (Faker $faker) {

    return [
        'id' => $faker->numberBetween(0,10000),
        'category'  => $faker->company,
        'name'  => $faker->name
    ];
});
