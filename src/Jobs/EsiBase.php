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

namespace Seatplus\Eveapi\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Seatplus\Eveapi\Esi\RetrieveFromEsiBase;
use Seatplus\Eveapi\Exceptions\EsiJobReleasedException;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;

abstract class EsiBase extends RetrieveFromEsiBase implements BaseJobInterface, ShouldBeUnique, ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Higher than default (3) to allow for rate-limit releases.
     */
    public int $tries = 10;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1 * 60, 5 * 60, 10 * 60, 15 * 60, 15 * 60, 15 * 60, 15 * 60, 15 * 60, 15 * 60];
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return implode(', ', $this->tags());
    }

    /**
     * EsiBase constructor.
     */
    public function __construct(
        public string $method,
        public string $endpoint,
        public string $version,
    ) {}

    #[\Override]
    abstract public function tags(): array;

    #[\Override]
    public function middleware(): array
    {
        return [
            new EsiProactiveRateLimitMiddleware,
            (new ThrottlesExceptionsWithRedis(80, 5 * 60))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    final public function handle(): void
    {
        try {
            DB::transaction(fn () => $this->executeJob());
        } catch (EsiJobReleasedException) {
            // Job was released back to the queue due to ESI rate/error limiting — not an error.
        } catch (Exception $exception) {
            report($exception);

            throw $exception;
        }
    }

    #[\Override]
    abstract public function executeJob(): void;

    #[\Override]
    public function getMethod(): string
    {
        return $this->method;
    }

    #[\Override]
    public function getVersion(): string
    {
        return $this->version;
    }

    #[\Override]
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
