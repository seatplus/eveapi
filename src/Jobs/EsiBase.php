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
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Esi\RetrieveFromEsiBase;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;
use Throwable;

abstract class EsiBase extends RetrieveFromEsiBase implements ShouldQueue, BaseJobInterface, ShouldBeUnique
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use RateLimitsEsiCalls;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [1 * 60, 5 * 60, 10 * 60];
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
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
    ) {
    }

    abstract public function tags(): array;

    public function middleware(): array
    {
        return [
            // TODO Add ESI Rate Limiting Middleware
        ];
    }

    final public function handle(): void
    {
        try {
            $this->executeJob();
        } catch (Exception $exception) {
            report($exception);

            throw $exception;
        }
    }

    abstract public function executeJob(): void;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        if ($exception instanceof MaxAttemptsExceededException) {
            return;
        }

        // if exception has getOriginalException Method
        if (method_exists($exception, 'getOriginalException')) {
            if ($exception->getOriginalException()?->getResponse()?->getReasonPhrase() === 'Forbidden') {
                $this->fail($exception);
            }
        }
    }
}
