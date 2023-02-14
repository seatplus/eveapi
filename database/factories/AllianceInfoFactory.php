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
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;

class AllianceInfoFactory extends Factory
{
    protected $model = AllianceInfo::class;

    public function definition()
    {
        return [
            'alliance_id' => $this->faker->numberBetween(99000000, 100000000),
            'creator_corporation_id' => $this->faker->numberBetween(98000000, 99000000),
            'creator_id' => $this->faker->numberBetween(90000000, 98000000),
            'date_founded' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
            'executor_corporation_id' => $this->faker->optional()->numberBetween(98000000, 99000000),
            'name' => $this->faker->name,
            'ticker' => $this->faker->bothify('[##??]'),
            'faction_id' => $this->faker->optional()->numberBetween(500000, 1000000),
        ];
    }
}
