<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020 Felix Huber
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
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Hydrate\Corporation\CorporationMemberTrackingHydrateBatch;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class UpdateCorporation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private ?int $corporation_id = null)
    {
    }

    public function handle()
    {
        if ($this->corporation_id) {
            return $this->dispatchUpdate($this->corporation_id, 'high');
        }

        return RefreshToken::with('corporation', 'character.roles')
            ->cursor()
            ->map(fn ($token) => $token->corporation->corporation_id)
            ->unique()
            ->each(fn ($corporation_id) => $this->dispatchUpdate($corporation_id));
    }

    private function execute(JobContainer $job_container, int $corporation_id)
    {
        $corporation = optional(CorporationInfo::find($corporation_id))->name ?? $corporation_id;

        $success_message = sprintf('%s (corporation) updated.', $corporation);
        $batch_name = sprintf('%s (corporation) update batch', $corporation);

        $batch = Bus::batch([
            new CorporationMemberTrackingHydrateBatch($job_container),
        ])->then(fn (Batch $batch) => logger()->info($success_message)
        )->name($batch_name)->onQueue($job_container->queue)->allowFailures()->dispatch();
    }

    private function dispatchUpdate(int $corporation_id, string $queue = 'default')
    {
        $job_container = new JobContainer(['corporation_id' => $corporation_id, 'queue' => $queue]);

        $this->execute($job_container, $corporation_id);
    }
}
