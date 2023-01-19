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

namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class UpdateCorporation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private FindCorporationRefreshToken $findCorporationRefreshToken;

    public function __construct(
        public ?int $corporation_id = null,
    ) {
        $this->findCorporationRefreshToken = new FindCorporationRefreshToken();
    }

    public function middleware()
    {
        return  [
            (new RateLimitedWithRedis('corporation_batch'))->dontRelease(),
        ];
    }

    public function handle()
    {
        if ($this->corporation_id) {
            $this->execute($this->corporation_id, 'high');
        } else {
            RefreshToken::with('corporation', 'character.roles')
                ->cursor()
                ->map(fn ($token) => $token->corporation->corporation_id)
                ->unique()
                ->each(fn ($corporation_id) => $this->execute($corporation_id));
        }
    }

    private function execute(int $corporation_id, string $queue = 'default')
    {
        $corporation = optional(CorporationInfo::find($corporation_id))->name ?? $corporation_id;

        $success_message = sprintf('%s (corporation) updated.', $corporation);
        $batch_name = sprintf('%s (corporation) update batch', $corporation);

        $batch = Bus::batch($this->getBatchJobs($corporation_id))
            ->then(fn (Batch $batch) => logger()->info($success_message))
            ->name($batch_name)
            ->onQueue($queue)
            ->allowFailures()
            ->dispatch();
    }

    private function getBatchJobs(int $corporation_id): array
    {
        return collect()
            ->merge($this->addCorporationDivisionsJobs($corporation_id))
            ->merge($this->addCorporationMemberTrackingJobs($corporation_id))
            ->merge($this->addCorporationWalletHydrateBatch($corporation_id))
            ->values()
            ->toArray();
    }

    private function addCorporationDivisionsJobs(int $corporation_id): array
    {
        if (! call_user_func_array($this->findCorporationRefreshToken, [$corporation_id, 'esi-corporations.read_divisions.v1', 'Director'])) {
            return [];
        }

        return [
            new CorporationDivisionsJob($corporation_id),
        ];
    }

    private function addCorporationMemberTrackingJobs(int $corporation_id): array
    {
        if (! call_user_func_array($this->findCorporationRefreshToken, [$corporation_id, 'esi-corporations.track_members.v1', 'Director'])) {
            return [];
        }

        return [
            new CorporationMemberTrackingJob($corporation_id),
        ];
    }

    private function addCorporationWalletHydrateBatch(int $corporation_id): array
    {
        if (! call_user_func_array($this->findCorporationRefreshToken, [$corporation_id, head(config('eveapi.scopes.corporation.wallet')), ['Accountant', 'Junior_Accountant']])) {
            return [];
        }

        return [
            [
                new CorporationBalanceJob($corporation_id),
                new CorporationWalletJournalJob($corporation_id),
            ],
        ];
    }
}
