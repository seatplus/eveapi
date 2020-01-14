<?php

use Seatplus\Eveapi\Models\Universe\Constellation;
use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Universe\Region;

$factory->define(Constellation::class, function (Faker $faker) {

    return [
        'region_id' => factory(Region::class),
        'name' => $faker->name,
        'constellation_id'  => $faker->numberBetween(20000000,22000000),
    ];
});

$factory->state(Constellation::class, 'noRegion', function (Faker $faker) {
    return [
        'region_id' => $faker->numberBetween(10000000,12000000),
    ];
});
