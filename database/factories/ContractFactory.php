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
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Location;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    #[\Override]
    public function definition()
    {
        return [
            'contract_id' => fake()->numberBetween(60000000, 68000000),
            'acceptor_id' => fake()->numberBetween(9000000, 98000000),
            'assignee_id' => fake()->numberBetween(9000000, 98000000),
            'availability' => fake()->randomElement(['public', 'personal', 'corporation', 'alliance']),
            'date_expired' => carbon()->addDay(),
            'date_issued' => carbon()->subDay(),
            'for_corporation' => fake()->boolean,
            'issuer_corporation_id' => CorporationInfo::factory(),
            'issuer_id' => fake()->numberBetween(9000000, 98000000),
            'status' => fake()->randomElement(['outstanding', 'in_progress', 'finished_issuer', 'finished_contractor', 'finished', 'cancelled', 'rejected', 'failed', 'deleted', 'reversed']),
            'type' => fake()->randomElement(['unknown', 'item_exchange', 'auction', 'courier', 'loan']),

            // optionals
            'buyout' => fake()->randomFloat(),
            'collateral' => fake()->randomFloat(),
            'date_accepted' => carbon()->addHour(),
            'date_completed' => carbon()->addHours(2),
            'days_to_complete' => fake()->randomDigitNotNull,
            'price' => fake()->randomFloat(),
            'reward' => fake()->randomFloat(),
            'end_location_id' => Location::factory()->withStation(),
            'start_location_id' => Location::factory()->withStation(),
            'title' => fake()->text(),
            'volume' => fake()->randomFloat(),
        ];
    }
}
