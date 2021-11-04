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
use Seatplus\Eveapi\Models\Contracts\Contract;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Location;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition()
    {
        return [
            'contract_id' => $this->faker->numberBetween(60000000, 68000000),
            'acceptor_id' => $this->faker->numberBetween(9000000, 98000000),
            'assignee_id' => $this->faker->numberBetween(9000000, 98000000),
            'availability' => $this->faker->randomElement(['public', 'personal', 'corporation', 'alliance']),
            'date_expired' => carbon()->addDay(),
            'date_issued' => carbon()->subDay(),
            'for_corporation' => $this->faker->boolean,
            'issuer_corporation_id' => CorporationInfo::factory(),
            'issuer_id' => $this->faker->numberBetween(9000000, 98000000),
            'status' => $this->faker->randomElement(['outstanding', 'in_progress', 'finished_issuer', 'finished_contractor', 'finished', 'cancelled', 'rejected', 'failed', 'deleted', 'reversed']),
            'type' => $this->faker->randomElement(['unknown', 'item_exchange', 'auction', 'courier', 'loan']),

            //optionals
            'buyout' => $this->faker->randomFloat(),
            'collateral' => $this->faker->randomFloat(),
            'date_accepted' => carbon()->addHour(),
            'date_completed' => carbon()->addHours(2),
            'days_to_complete' => $this->faker->randomDigitNotNull,
            'price' => $this->faker->randomFloat(),
            'reward' => $this->faker->randomFloat(),
            'end_location_id' => Location::factory()->withStation(),
            'start_location_id' => Location::factory()->withStation(),
            'title' => $this->faker->text(),
            'volume' => $this->faker->randomFloat(),
        ];
    }
}
