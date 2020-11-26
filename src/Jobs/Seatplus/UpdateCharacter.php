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
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Character\CharacterInfoJob;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterAssetsHydrateBatch;
use Seatplus\Eveapi\Jobs\Hydrate\Character\CharacterRolesHydrateBatch;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Pipes\CharacterRolesPipe;

class UpdateCharacter implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $pipes = [
        CharacterRolesPipe::class,
    ];

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken|null
     */
    private ?RefreshToken $refresh_token;

    public function __construct(?RefreshToken $refresh_token = null)
    {
        $this->refresh_token = $refresh_token;
    }

    public function handle()
    {
        if ($this->refresh_token) {
            return $this->execute($this->refresh_token, 'high');
        }

        return RefreshToken::cursor()->each(function ($token) {
            $this->execute($token);
        });
    }

    private function execute(RefreshToken $refresh_token, string $queue = 'default')
    {
        $job_container = new JobContainer(['refresh_token' => $refresh_token, 'queue' => $queue]);

        $character = optional($refresh_token->refresh()->character)->name ?? $refresh_token->character_id;
        $success_message = sprintf('Character update batch of %s processed!', $character);
        $batch_name = sprintf('%s (character) update batch', $character);

        Bus::batch([
            new CharacterInfoJob($job_container), // Public endpoint hence no hydration or added logic required
            new CharacterAssetsHydrateBatch($job_container),
            new CharacterRolesHydrateBatch($job_container)
        ])->then(fn (Batch $batch) => logger()->info($success_message)
        )->name($batch_name)->onQueue($queue)->allowFailures()->dispatch();

    }
}
