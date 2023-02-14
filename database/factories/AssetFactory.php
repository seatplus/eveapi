<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Universe\Type;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition()
    {
        return [
            'assetable_id' => $this->faker->numberBetween(), //factory(CharacterInfo::class),
            'assetable_type' => CharacterInfo::class, //$this->faker->randomElement([CharacterInfo::class, CorporationInfo::class]),
            'item_id' => $this->faker->unique()->randomNumber(),
            'is_blueprint_copy' => false,
            'is_singleton' => $this->faker->boolean,
            'location_flag' => $this->faker->randomElement($this->getLocationFlagArray()),
            'location_id' => $this->faker->randomNumber(),
            'location_type' => $this->faker->randomElement(['station', 'solar_system', 'other']),
            'quantity' => $this->faker->randomDigit,
            'type_id' => $this->faker->numberBetween(5, 10000),
        ];
    }

    public function withName()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => implode(' ', $this->faker->unique()->words(2)),
            ];
        });
    }

    public function withType()
    {
        return $this->state(function (array $attributes) {
            return [
                'type_id' => Type::factory(),
            ];
        });
    }

    private function getLocationFlagArray(): array
    {
        return [
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
            'SubSystemSlot7', 'Unlocked', 'Wardrobe',
        ];
    }
}
