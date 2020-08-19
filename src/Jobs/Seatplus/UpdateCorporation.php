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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Pipes\Corporation\CorporationMemberTrackingPipe;

class UpdateCorporation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $pipes = [
        CorporationMemberTrackingPipe::class
    ];

    private ?int $corporation_id;

    public function __construct(?int $corporation_id = null)
    {
        $this->corporation_id = $corporation_id;
    }

    public function handle()
    {
        if($this->corporation_id)
            return $this->dispatchUpdate($this->corporation_id, 'high');

        return RefreshToken::with('corporation', 'character.roles')
            ->cursor()
            ->map(fn($token) => $token->corporation->corporation_id)
            ->unique()
            ->each(fn($corporation_id) => $this->dispatchUpdate($corporation_id));
    }

    private function execute(JobContainer $job_container, string $success_message)
    {
        app(Pipeline::class)
            ->send($job_container)
            ->through($this->pipes)
            ->then(
                fn ($jobcontainer) => logger()->info($success_message)
            );
    }

    private function dispatchUpdate(int $corporation_id, string $queue = 'default')
    {
        $job_container = new JobContainer(['corporation_id' => $corporation_id, 'queue' => $queue]);

        $success_message = sprintf('%s (corporation) updated.',
            optional(CorporationInfo::find($corporation_id))->name ?? $corporation_id,
        );

        $this->execute($job_container, $success_message);
    }

}
