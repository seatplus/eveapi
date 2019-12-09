<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Groups;
use Seatplus\Eveapi\Models\Universe\Types;

$factory->define(Groups::class, function (Faker $faker) {

    return [
        'group_id' => $faker->numberBetween(0,10000),
        'category_id' => $faker->numberBetween(0,10000),
        'name'  => $faker->name,
        'published' => $faker->boolean
    ];
});
