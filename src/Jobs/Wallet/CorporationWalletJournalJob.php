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

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Wallet\Balance;

class CorporationWalletJournalJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $corporation_id;

    public function __construct(
        private JobContainer $job_container
    ) {
        $this->corporation_id = $this->job_container->getCorporationId();
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return sprintf(
            'Corporation wallet journal dispatcher for corpoation %s via %s (%s)',
            $this->job_container->getRefreshToken()->corporation->name,
            $this->job_container->getRefreshToken()->character->name,
            $this->job_container->getRefreshToken()->character_id
        );
    }

    public function tags(): array
    {
        return [
            'corporation',
            'corporation_id: ' . $this->corporation_id,
            'wallet',
            'journals',
        ];
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        Balance::query()
            ->whereHasMorph(
                'balanceable',
                CorporationInfo::class,
                fn (Builder $query) => $query->where('corporation_id', $this->corporation_id)
            )
            ->cursor()
            ->each(
                fn ($wallet) => $this->batching()
                ? $this->handleBatching($wallet->division)
                : $this->handleNonBatching($wallet->division)
            );
    }

    private function handleBatching(int $division): void
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...

            return;
        }

        $this->batch()->add([
            new CorporationWalletJournalByDivisionJob($this->job_container, $division),
        ]);
    }

    private function handleNonBatching(int $division): void
    {
        CorporationWalletJournalByDivisionJob::dispatch($this->job_container, $division)->onQueue($this->queue);
    }
}
