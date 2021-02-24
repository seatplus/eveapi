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
use Seatplus\Eveapi\Models\Alliance\AllianceInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;

class CorporationInfoFactory extends Factory
{
    protected $model = CorporationInfo::class;

    public function definition()
    {
        return [
            'corporation_id'  => $this->faker->numberBetween(98000000, 99000000),
            'ticker' => $this->faker->bothify('[##??]'),
            'name'            => $this->faker->name,
            'member_count' => $this->faker->randomDigitNotNull,
            'ceo_id' => $this->faker->numberBetween(90000000, 98000000),
            'creator_id' => $this->faker->numberBetween(90000000, 98000000),
            'tax_rate' => $this->faker->randomFloat(2, 0, 1),
            'alliance_id' => $this->faker->optional()->numberBetween(99000000, 100000000),
        ];
    }

    public function withAlliance()
    {
        return $this->state(function (array $attributes) {
            return [
                'alliance_id' => AllianceInfo::factory(),
            ];
        });
    }
}