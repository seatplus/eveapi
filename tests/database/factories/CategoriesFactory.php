<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Categories;

$factory->define(Categories::class, function (Faker $faker) {

    return [
        'category_id' => $faker->numberBetween(0,10000),
        'name'  => $faker->name,
        'published' => $faker->boolean
    ];
});
