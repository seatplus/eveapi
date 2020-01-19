<?php

use Faker\Generator as Faker;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Type;

$factory->define(CharacterAsset::class, function (Faker $faker) {

    $location_flag_array = [
        'AssetSafety', 'AutoFit', 'BoosterBay', 'Cargo', 'CorpseBay', 'Deliveries', 'DroneBay', 'FighterBay',
        'FighterTube0', 'FighterTube1', 'FighterTube2', 'FighterTube3', 'FighterTube4', 'FleetHangar', 'Hangar',
        'HangarAll', 'HiSlot0', 'HiSlot1', 'HiSlot2', 'HiSlot3', 'HiSlot4', 'HiSlot5', 'HiSlot6', 'HiSlot7',
        'HiddenModifiers', 'Implant', 'LoSlot0', 'LoSlot1', 'LoSlot2', 'LoSlot3', 'LoSlot4', 'LoSlot5', 'LoSlot6',
        'LoSlot7', 'Locked', 'MedSlot0', 'MedSlot1', 'MedSlot2', 'MedSlot3', 'MedSlot4', 'MedSlot5', 'MedSlot6',
        'MedSlot7', 'QuafeBay', 'RigSlot0', 'RigSlot1', 'RigSlot2', 'RigSlot3', 'RigSlot4', 'RigSlot5', 'RigSlot6',
        'RigSlot7', 'ShipHangar', 'Skill', 'SpecializedAmmoHold', 'SpecializedCommandCenterHold', 'SpecializedFuelBay',
        'SpecializedGasHold', 'SpecializedIndustrialShipHold', 'SpecializedLargeShipHold', 'SpecializedMaterialBay',
        'SpecializedMediumShipHold', 'SpecializedMineralHold', 'SpecializedOreHold', 'SpecializedPlanetaryCommoditiesHold',
        'SpecializedSalvageHold', 'SpecializedShipHold', 'SpecializedSmallShipHold', 'SubSystemBay', 'SubSystemSlot0',
        'SubSystemSlot1', 'SubSystemSlot2', 'SubSystemSlot3', 'SubSystemSlot4', 'SubSystemSlot5', 'SubSystemSlot6',
        'SubSystemSlot7', 'Unlocked', 'Wardrobe'
    ];

    return [
        'character_id' => factory(CharacterInfo::class),
        'item_id' => $faker->unique()->randomNumber(),
        'is_blueprint_copy' => false,
        'is_singleton' => $faker->boolean,
        'location_flag'  => $faker->randomElement($location_flag_array),
        'location_id' => $faker->randomNumber(),
        'location_type' => $faker->randomElement(['station', 'solar_system', 'other']),
        'quantity' => $faker->randomDigit,
        'type_id' => factory(Type::class)
    ];
});

$factory->state(CharacterAsset::class, 'withName', function (Faker $faker) {
    return [
        'name'  => $faker->name,
    ];
});

$factory->state(CharacterAsset::class, 'withoutType', function (Faker $faker) {
    return [
        'type_id' => $faker->numberBetween(5,10000)
    ];
});
