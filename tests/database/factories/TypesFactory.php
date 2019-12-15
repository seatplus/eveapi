<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Type;

$factory->define(Type::class, function (Faker $faker) {

    return [
        'type_id' => $faker->numberBetween(0,10000),
        'group_id' => $faker->numberBetween(0,10000),
        'description'  => $faker->company,
        'name'  => $faker->name,
        'published' => $faker->boolean
    ];
});
