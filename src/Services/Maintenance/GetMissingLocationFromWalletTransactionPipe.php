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

namespace Seatplus\Eveapi\Services\Maintenance;

use Closure;
use Seatplus\Eveapi\Jobs\Universe\ResolveLocationJob;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Wallet\WalletTransaction;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class GetMissingLocationFromWalletTransactionPipe
{
    public function handle($payload, Closure $next)
    {
        WalletTransaction::doesntHave('location')
            ->inRandomOrder()
            ->get()
            ->unique('location_id')
            ->each(function ($wallet_transaction) {
                $refresh_token = null;

                if ($wallet_transaction->wallet_transactionable_type === CharacterInfo::class) {
                    $refresh_token = RefreshToken::find($wallet_transaction->wallet_transactionable_id);
                }

                if ($wallet_transaction->wallet_transactionable_type === CorporationInfo::class) {
                    $find_corporation_refresh_token = new FindCorporationRefreshToken;

                    $refresh_token = $find_corporation_refresh_token($this->wallet_transaction->wallet_transactionable_id, 'esi-universe.read_structures.v1', 'Director') ?? $this->getRandomRefreshToken($wallet_transaction);
                }

                if ($refresh_token) {
                    dispatch(new ResolveLocationJob($wallet_transaction->location_id, $refresh_token))->onQueue('high');
                }
            });

        return $next($payload);
    }

    private function getRandomRefreshToken(WalletTransaction $wallet_transaction)
    {
        $random_character = $wallet_transaction->wallet_transactionable->characters->random();

        return RefreshToken::find($random_character->character_id);
    }
}
