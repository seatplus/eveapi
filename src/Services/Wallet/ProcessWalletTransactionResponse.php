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

namespace Seatplus\Eveapi\Services\Wallet;

use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;

class ProcessWalletTransactionResponse
{
    public function __construct(
        private int $wallet_transactionable_id,
        private string $wallet_transactionable_type,
        private ?int $division=null
    ) {
    }

    public function execute(EsiResponse $response): int
    {
        return collect($response)
            ->each(fn ($entry) => WalletTransaction::firstOrCreate(
                [
                    'transaction_id' => $entry->transaction_id, ],
                [
                    'wallet_transactionable_id' => $this->wallet_transactionable_id,
                    'wallet_transactionable_type' => $this->wallet_transactionable_type,
                    'division' => $this->division,

                    // required props
                    'client_id' => $entry->client_id,
                    'date' => carbon($entry->date),
                    'is_buy' => $entry->is_buy,
                    'is_personal' => $entry->is_personal,
                    'journal_ref_id' => $entry->journal_ref_id,
                    'location_id' => $entry->location_id,
                    'quantity' => $entry->quantity,
                    'type_id' => $entry->type_id,
                    'unit_price' => $entry->unit_price,
                ]
            ))
            ->last()->transaction_id;
    }
}
