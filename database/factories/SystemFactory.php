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
use Seatplus\Eveapi\Models\Universe\Constellation;
use Seatplus\Eveapi\Models\Universe\System;

class SystemFactory extends Factory
{
    protected $model = System::class;

    public function definition()
    {
        return [
            'system_id' => $this->faker->numberBetween(30000000, 32000000),
            'name' => $this->faker->name,
            'constellation_id' => Constellation::factory(),
            'security_class' => $this->faker->optional()->word,
            'security_status' => $this->faker->randomFloat(1, -1,  1),
        ];
    }

    public function noConstellation()
    {
        return $this->state(fn () => [
            'constellation_id' => $this->faker->numberBetween(20000000, 22000000),
        ]);
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    /*public function configure()
    {
        return $this->afterMaking(function (System $sytem) {
            //
        })->afterCreating(function (System $sytem) {

        });
    }*/
}
