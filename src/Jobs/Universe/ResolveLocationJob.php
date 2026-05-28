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

namespace Seatplus\Eveapi\Jobs\Universe;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\ResolveLocation\ResolveLocationService;

class ResolveLocationJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 7200;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return implode('; ', $this->tags());
    }

    public function __construct(
        public int $location_id,
        public ?RefreshToken $refresh_token = null
    ) {}

    public function tags(): array
    {
        if ($this->refresh_token) {
            return [
                'location_resolve',
                'location_id:'.$this->location_id,
                'via character: '.$this->refresh_token->character_id,
            ];
        }

        return [
            'location_resolve',
            'location_id:'.$this->location_id,
        ];
    }

    public function handle(): void
    {

        ResolveLocationService::make($this->refresh_token)->handle($this->location_id);
    }
}
