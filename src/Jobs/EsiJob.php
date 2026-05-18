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
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\EsiErrorLimitedException;
use Seatplus\EsiClient\Exceptions\EsiRateLimitedException;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;

abstract class EsiJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     * Higher than default to allow for rate-limit releases.
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
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            new EsiProactiveRateLimitMiddleware,
            (new ThrottlesExceptionsWithRedis(80, 5 * 60))
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    /**
     * Laravel injects EsiClient and the token-refresh service via the service container.
     *
     * Auth is applied automatically when getRefreshToken() returns a token.
     * Override getRefreshToken() in authenticated jobs — return null for public endpoints.
     */
    final public function handle(EsiClient $esi, GetUpToDateRefreshTokenService $tokenService): void
    {
        $token = $this->getRefreshToken();

        if ($token !== null) {
            $upToDate = ($tokenService)($token);
            $esi = $esi->withToken($upToDate->getRawOriginal('token'));
        }

        try {
            DB::transaction(fn () => $this->executeJob($esi));
        } catch (EsiRateLimitedException|EsiErrorLimitedException $e) {
            $this->release($e->retryAfter);
        } catch (Exception $e) {
            report($e);

            throw $e;
        }
    }

    /**
     * Return the refresh token for authenticated endpoints.
     * Return null for public (unauthenticated) ESI endpoints.
     */
    public function getRefreshToken(): ?RefreshToken
    {
        return null;
    }

    /**
     * Execute the ESI job. The $esi client is already authenticated when getRefreshToken() returns a token.
     */
    abstract protected function executeJob(EsiClient $esi): void;

    /**
     * Get the tags that should be assigned to the job.
     */
    abstract public function tags(): array;
}
