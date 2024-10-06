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
use Seatplus\Eveapi\Models\Contracts\ContractItem;
use Seatplus\Eveapi\Models\Universe\Type;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Seatplus\Eveapi\Models\Contracts\ContractItem>
 */
class ContractItemFactory extends Factory
{
    protected $model = ContractItem::class;

    #[\Override]
    public function definition()
    {
        return [
            'record_id' => fake()->unique()->randomNumber(8),
            'contract_id' => fake()->numberBetween(60000000, 68000000),
            'is_included' => fake()->boolean,
            'is_singleton' => fake()->boolean,
            'type_id' => Type::factory(),
            'quantity' => fake()->randomDigit(),
        ];
    }

    public function withoutType()
    {
        return $this->state(fn () => ['type_id' => fake()->numberBetween(0, 10000)]);
    }
}
