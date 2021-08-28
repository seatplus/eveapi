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

namespace Seatplus\Eveapi\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\System;

class StationFactory extends Factory
{
    protected $model = Station::class;

    public function definition()
    {
        return [
            'station_id' => $this->faker->numberBetween(60000000, 64000000),
            'name' => $this->faker->name,
            'owner_id' => $this->faker->optional()->numberBetween(98000000, 99000000),
            'system_id' => System::factory(),
            'type_id' => $this->faker->numberBetween(0, 10000),
            'race_id' => $this->faker->optional()->numberBetween(98000000, 99000000),
            'reprocessing_efficiency' => $this->faker->randomNumber(),
            'reprocessing_stations_take' => $this->faker->randomNumber(),
            'max_dockable_ship_volume' => $this->faker->randomNumber(),
            'office_rental_cost' => $this->faker->randomDigit,

        ];
    }

    public function noSystem()
    {
        return $this->state(fn () => [
            'system_id' => $this->faker->numberBetween(30000000, 31000000),
        ]);
    }
}
