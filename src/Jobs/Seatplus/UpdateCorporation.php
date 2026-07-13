<?php

declare(strict_types=1);

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
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimitedWithRedis;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Jobs\Corporation\CorporationDivisionsJob;
use Seatplus\Eveapi\Jobs\Corporation\CorporationMemberTrackingJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationBalanceJob;
use Seatplus\Eveapi\Jobs\Wallet\CorporationWalletJournalJob;
use Seatplus\Eveapi\Models\BatchStatistic;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\FindCorporationRefreshToken;

class UpdateCorporation implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    private FindCorporationRefreshToken $findCorporationRefreshToken;

    public function __construct(
        public ?int $corporationId = null,
    ) {
        $this->findCorporationRefreshToken = new FindCorporationRefreshToken;
    }

    public function middleware(): array
    {
        return [
            (new RateLimitedWithRedis('corporation_batch'))->dontRelease(),
        ];
    }

    public function uniqueId(): string
    {
        return (string) ($this->corporationId ?? 'all');
    }

    public function handle(): void
    {
        if ($this->corporationId) {
            $this->execute($this->corporationId, 'high');
        } else {
            RefreshToken::with(['corporation', 'character.roles'])
                ->cursor()
                ->map(fn (RefreshToken $token) => $token->corporation?->corporation_id)
                ->filter()
                ->unique()
                ->each(fn (int $corporationId) => $this->execute($corporationId));
        }
    }

    private function execute(int $corporationId, string $queue = 'default'): void
    {
        $corporation = optional(CorporationInfo::find($corporationId))->name ?? $corporationId;

        $batchName = sprintf('%s (corporation) update batch', $corporation);

        $batch = Bus::batch($this->getBatchJobs($corporationId))
            ->then(fn (Batch $batch) => BatchStatistic::where('batch_id', $batch->id)->update(['finished_at' => now()]))
            ->name($batchName)
            ->onQueue($queue)
            ->allowFailures()
            ->dispatch();

        BatchStatistic::createEntry($batch);
    }

    private function getBatchJobs(int $corporationId): array
    {
        return collect()
            ->merge($this->addCorporationDivisionsJobs($corporationId))
            ->merge($this->addCorporationMemberTrackingJobs($corporationId))
            ->merge($this->addCorporationWalletHydrateBatch($corporationId))
            ->values()
            ->toArray();
    }

    private function addCorporationDivisionsJobs(int $corporationId): array
    {
        if (! call_user_func_array($this->findCorporationRefreshToken, [$corporationId, 'esi-corporations.read_divisions.v1', 'Director'])) {
            return [];
        }

        return [
            new CorporationDivisionsJob($corporationId),
        ];
    }

    private function addCorporationMemberTrackingJobs(int $corporationId): array
    {
        if (! call_user_func_array($this->findCorporationRefreshToken, [$corporationId, 'esi-corporations.track_members.v1', 'Director'])) {
            return [];
        }

        return [
            new CorporationMemberTrackingJob($corporationId),
        ];
    }

    private function addCorporationWalletHydrateBatch(int $corporationId): array
    {
        if (! call_user_func_array($this->findCorporationRefreshToken, [$corporationId, head(config('eveapi.scopes.corporation.wallet')), ['Accountant', 'Junior_Accountant']])) {
            return [];
        }

        return [
            [
                new CorporationBalanceJob($corporationId),
                new CorporationWalletJournalJob($corporationId),
            ],
        ];
    }
}
