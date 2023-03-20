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
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition()
    {
        return [
            'transaction_id' => $this->faker->unique()->randomNumber + 1,
            'wallet_transactionable_id' => $this->faker->numberBetween(90_000_000, 98_000_000),
            'wallet_transactionable_type' => CharacterInfo::class,

            'client_id' => $this->faker->numberBetween(90_000_000, 98_000_000),
            'date' => $this->faker->iso8601,
            'is_buy' => $this->faker->boolean(),
            'is_personal' => $this->faker->boolean(),
            'journal_ref_id' => $this->faker->randomNumber(8),
            'location_id' => $this->faker->randomNumber(6),
            'quantity' => $this->faker->randomNumber(),
            'type_id' => $this->faker->numberBetween(90_000_000, 98_000_000),
            'unit_price' => $this->faker->randomFloat(2),
        ];
    }
}
