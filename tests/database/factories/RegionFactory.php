<?php

use Seatplus\Eveapi\Models\Universe\Constellation;
use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Region;

$factory->define(Region::class, function (Faker $faker) {

    return [
        'region_id' => $faker->numberBetween(10000000,12000000),
        'name' => $faker->name,
        'description'  => $faker->optional()->text(),
    ];
});
