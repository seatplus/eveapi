<?php


use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

$factory->define(CharacterInfo::class, function (Faker $faker) {

    return [
        'character_id'    => $faker->numberBetween(9000000, 98000000),
        'name'            => $faker->name,
        'corporation_id'  => $faker->numberBetween(98000000, 99000000),
        'birthday'        => $faker->iso8601($max = 'now'),
        'gender'          => $faker->randomElement(['male', 'female']),
        'race_id'         => $faker->randomDigitNotNull,
        'bloodline_id'    => $faker->randomDigitNotNull,
    ];
});

$factory->afterCreating(CharacterInfo::class, function ($character_info, $faker) {

    $character_info->corporation()->associate(factory(CorporationInfo::class)->create());
    $character_info->alliance()->associate(factory(AllianceInfo::class)->create());
});