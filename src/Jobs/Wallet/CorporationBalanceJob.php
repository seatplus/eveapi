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

namespace Seatplus\Eveapi\Jobs\Wallet;

use Illuminate\Support\Collection;
use Seatplus\Eveapi\Esi\HasCorporationRoleInterface;
use Seatplus\Eveapi\Esi\HasPathValuesInterface;
use Seatplus\Eveapi\Esi\HasRequiredScopeInterface;
use Seatplus\Eveapi\Jobs\EsiBase;
use Seatplus\Eveapi\Jobs\Middleware\HasRequiredScopeMiddleware;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;
use Seatplus\Eveapi\Traits\HasCorporationRole;
use Seatplus\Eveapi\Traits\HasPages;
use Seatplus\Eveapi\Traits\HasPathValues;
use Seatplus\Eveapi\Traits\HasRequiredScopes;

class CorporationBalanceJob extends EsiBase implements HasPathValuesInterface, HasRequiredScopeInterface, HasCorporationRoleInterface
{
    use HasPathValues;
    use HasRequiredScopes;
    use HasPages;
    use HasCorporationRole;

    public function __construct(
        public int $corporation_id,
    ) {
        parent::__construct(
            method: 'get',
            endpoint: '/corporations/{corporation_id}/wallets/',
            version: 'v1',
        );

        $this->setRequiredScope(head(config('eveapi.scopes.corporation.wallet')));

        $this->setPathValues([
            'corporation_id' => $this->corporation_id,
        ]);

        $this->setCorporationRoles(['Accountant', 'Junior_Accountant']);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new HasRequiredScopeMiddleware,
            ...parent::middleware(),
        ];
    }

    public function tags(): array
    {
        return [
            'corporation',
            'corporation_id: '.$this->corporation_id,
            'balances',
        ];
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function executeJob(): void
    {
        $response = $this->retrieve();

        if ($response->isCachedLoad()) {
            return;
        }

        $corporation_balances = collect($response)
            ->map(
                fn ($wallet) => [
                    'balanceable_id' => $this->corporation_id,
                    'balanceable_type' => CorporationInfo::class,
                    'division' => data_get($wallet, 'division'),
                    'balance' => data_get($wallet, 'balance'),
                ]
            );

        Balance::upsert(
            $corporation_balances->toArray(),
            ['balanceable_id', 'balanceable_type', 'division'],
            ['balance']
        );

        $this->dispatchDivisionJobs($corporation_balances);
    }

    private function dispatchDivisionJobs(Collection $corporation_balances)
    {
        $corporation_balances->each(function ($balance) {
            CorporationWalletJournalByDivisionJob::dispatch($this->corporation_id, $balance['division'])->onQueue('high');
            CorporationWalletTransactionByDivisionJob::dispatch($this->corporation_id, $balance['division'])->onQueue('high');
        });
    }
}
