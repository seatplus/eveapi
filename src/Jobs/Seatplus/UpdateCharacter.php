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
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Pipes\CharacterAssetsPipe;
use Seatplus\Eveapi\Services\Pipes\CharacterInfoPipe;
use Seatplus\Eveapi\Services\Pipes\CharacterRolesPipe;

class UpdateCharacter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $pipes = [
        CharacterInfoPipe::class,
        CharacterAssetsPipe::class,
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

        app(Pipeline::class)
            ->send($job_container)
            ->through($this->pipes)
            ->then(
                fn ($jobcontainer) => logger()->info(sprintf('RefreshToken of %s updated!',
                        optional($refresh_token->refresh()->character)->name ?? $refresh_token->character_id
                    )
                )

            );
    }
}
