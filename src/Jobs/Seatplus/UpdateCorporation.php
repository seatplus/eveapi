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
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Pipes\Corporation\CorporationMemberTrackingPipe;

class UpdateCorporation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $pipes = [
        CorporationMemberTrackingPipe::class
    ];

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken|null
     */
    private ?RefreshToken $refresh_token;

    private Collection $processed_corporation_ids;

    public function __construct(?RefreshToken $refresh_token = null)
    {
        $this->refresh_token = $refresh_token;
        $this->processed_corporation_ids = collect();
    }

    public function handle()
    {

        if ($this->refresh_token) {
            return $this->handleDirectUpdate();
        }

        return RefreshToken::with('corporation', 'character.roles')
            ->cursor()
            ->shuffle()
            ->each(function (RefreshToken $token) {
                // perform director level update
                if(optional($token->character)->roles ? $token->character->roles->hasRole('roles','Director') : false)
                    $this->directorUpdate($token);
            })
            ->reject(fn($token) => $this->processed_corporation_ids->contains($token->corporation_id))
            ->each(fn($token) => $this->nonDirectorUpdate($token));
    }

    private function directorUpdate(RefreshToken $refresh_token, string $queue = 'default')
    {
        if($this->hasAlreadyProcessed($refresh_token)) return;

        $job_container = new JobContainer(['refresh_token' => $refresh_token, 'queue' => $queue]);

        $success_message = sprintf('%s (corporation) updated, using director refresh_token of %s',
            optional($refresh_token->refresh()->corporation)->name ?? $refresh_token->corporation_id,
            optional($refresh_token->refresh()->character)->name ?? $refresh_token->character_id
        );

        $this->execute($job_container, $success_message);
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

    private function hasAlreadyProcessed(RefreshToken $refresh_token)
    {
        $corporation_id = $refresh_token->corporation_id;

        return $this->processed_corporation_ids->contains($corporation_id) ?: $this->processed_corporation_ids->push($corporation_id)->isEmpty();
    }

    private function nonDirectorUpdate(RefreshToken $refresh_token, string $queue = 'default')
    {
        $job_container = new JobContainer(['refresh_token' => $refresh_token, 'queue' => $queue]);

        $success_message = sprintf('%s (corporation) updated, using a non director refresh_token of %s',
            optional($refresh_token->refresh()->corporation)->name ?? $refresh_token->corporation_id,
            optional($refresh_token->refresh()->character)->name ?? $refresh_token->character_id
        );

        $this->execute($job_container, $success_message);
    }

    private function handleDirectUpdate()
    {
        return $this->refresh_token->hasScope('Director')
            ? $this->directorUpdate($this->refresh_token, 'high')
            : $this->nonDirectorUpdate($this->refresh_token, 'high');
    }
}
