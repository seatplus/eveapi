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

namespace Seatplus\Eveapi\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Facades\DB;
use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiClient\Exceptions\EsiErrorLimitedException;
use Seatplus\EsiClient\Exceptions\EsiRateLimitedException;
use Seatplus\Eveapi\Exceptions\InvalidRefreshTokenException;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Esi\GetUpToDateRefreshTokenService;
use Seatplus\Eveapi\Services\Esi\InvalidTokenThrottleService;
use Seatplus\Eveapi\Services\Esi\RecordingEsiClient;

abstract class EsiJob implements ShouldBeUnique, ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * Absolute deadline, in minutes from first dispatch, for retrying a job — including
     * time spent waiting out rate-limit releases.
     */
    private const int RETRY_UNTIL_MINUTES = 30;

    /**
     * No fixed attempt cap. Rate-limit releases — from EsiProactiveRateLimitMiddleware, the
     * ThrottlesExceptions circuit breaker, and the EsiRateLimited/ErrorLimited catch in
     * handle() — are flow control, not failures; bounding them by a fixed $tries turned
     * "waiting for tokens" into MaxAttemptsExceeded (and cancelled batches). Retries are
     * instead bounded by retryUntil() (time) and $maxExceptions (genuine errors).
     */
    public int $tries = 0;

    /**
     * Genuine, uncaught exceptions tolerated before the job is failed. Only the rethrowing
     * `catch (Exception)` in handle() decrements this — caught paths (rate-limit release,
     * InvalidRefreshToken fail) and middleware releases do not — so throttling can never
     * fail a job, while a real ESI error still stops it after three attempts.
     */
    public int $maxExceptions = 3;

    /**
     * Seconds to wait between retries of a *genuine* (rethrown) exception. Rate-limit
     * releases pass their own explicit delay and never use this, so only maxExceptions
     * worth of entries are ever consumed.
     */
    public function backoff(): array
    {
        return [1 * 60, 5 * 60, 10 * 60];
    }

    /**
     * Retry deadline. Laravel computes this once on first dispatch and persists it across
     * attempts, so a throttled job keeps retrying until its tokens refill without ever
     * failing on attempt count.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(self::RETRY_UNTIL_MINUTES);
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
            new ThrottlesExceptionsWithRedis(80, 5 * 60)
                ->by('esiratelimit')
                ->backoff(5),
        ];
    }

    /**
     * Fully-qualified class name of the primary ESI operation this job calls.
     * Used to derive the rate-limit group (RATE_LIMIT_GROUP constant on the operation class).
     * Override in concrete jobs: protected const string OPERATION_CLASS = GetCharactersCharacterIdAssets::class;
     */
    protected const string OPERATION_CLASS = '';

    /**
     * Laravel injects EsiClient and the token-refresh service via the service container.
     *
     * The container resolves EsiClient as RecordingEsiClient (bound in EveapiServiceProvider::register()),
     * so every ESI call transparently records X-Ratelimit-Remaining into Redis.
     *
     * Auth is applied automatically when getRefreshToken() returns a token.
     * Override getRefreshToken() in authenticated jobs — return null for public endpoints.
     *
     * @see RecordingEsiClient
     * @see EveapiServiceProvider::register()
     */
    final public function handle(EsiClient $esi, GetUpToDateRefreshTokenService $tokenService, InvalidTokenThrottleService $throttle): void
    {
        $token = null;

        try {
            $token = $this->getRefreshToken();

            if ($token !== null) {
                $upToDate = $tokenService->get($token);
                $esi = $esi->withToken($upToDate->getRawOriginal('token'));
            }

            if ($esi instanceof RecordingEsiClient) {
                $esi->setContext(
                    $this->rateLimitGroup(),
                    $this->rateLimitCharacterId(),
                    $this->rateLimitMaxTokens(),
                    $this->rateLimitWindowSeconds(),
                );
            }

            if ($this->wrapExecuteJobInTransaction()) {
                DB::transaction(fn () => $this->executeJob($esi));
            } else {
                $this->executeJob($esi);
            }
        } catch (EsiRateLimitedException|EsiErrorLimitedException $e) {
            $this->release($e->retryAfter);
        } catch (InvalidRefreshTokenException $e) {
            if ($token !== null && $throttle->hit($token->character_id)) {
                $token->delete();
            }

            $this->fail($e);
        } catch (Exception $e) {
            report($e);

            throw $e;
        }
    }

    /**
     * Whether handle() should wrap the whole executeJob() call in a DB transaction.
     *
     * The default (true) keeps every job's historical behaviour: executeJob() runs inside a
     * single transaction. Buffered paging jobs (assets, wallet journal/transactions, contracts)
     * accumulate every ESI page into memory before a single final write, so wrapping the whole
     * method would hold the transaction — and its snapshot/row locks — open across every network
     * round-trip for no benefit. Those jobs return false here and open a narrow transaction around
     * only their final write themselves; the read-only paging then runs outside any transaction.
     */
    protected function wrapExecuteJobInTransaction(): bool
    {
        return true;
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
     * Returns the ESI rate-limit group for this job.
     * Derived automatically from OPERATION_CLASS::RATE_LIMIT_GROUP.
     * Falls back to 'global' when OPERATION_CLASS is not declared.
     */
    public function rateLimitGroup(): string
    {
        $op = static::OPERATION_CLASS;

        if ($op === '') {
            return 'global';
        }

        $group = defined("{$op}::RATE_LIMIT_GROUP") ? constant("{$op}::RATE_LIMIT_GROUP") : null;

        return is_string($group) && $group !== '' ? $group : 'global';
    }

    /**
     * Returns the character ID used as the rate-limit bucket owner.
     * Derived automatically from getRefreshToken(). Null for public endpoints.
     */
    public function rateLimitCharacterId(): ?int
    {
        return $this->getRefreshToken()?->character_id;
    }

    /**
     * The bucket capacity for this endpoint, from OPERATION_CLASS::RATE_LIMIT_MAX_TOKENS
     * (e.g. 150 for char-wallet). Null when the endpoint declares no quota — such jobs
     * are never proactively throttled rather than measured against a wrong denominator.
     */
    public function rateLimitMaxTokens(): ?int
    {
        $op = static::OPERATION_CLASS;

        if ($op === '') {
            return null;
        }

        $value = defined("{$op}::RATE_LIMIT_MAX_TOKENS") ? constant("{$op}::RATE_LIMIT_MAX_TOKENS") : null;

        return is_int($value) ? $value : null;
    }

    /**
     * The refill window in seconds, parsed from OPERATION_CLASS::RATE_LIMIT_WINDOW
     * (e.g. '15m' → 900). Null when unknown or unparseable.
     */
    public function rateLimitWindowSeconds(): ?int
    {
        $op = static::OPERATION_CLASS;

        if ($op === '') {
            return null;
        }

        $window = defined("{$op}::RATE_LIMIT_WINDOW") ? constant("{$op}::RATE_LIMIT_WINDOW") : null;

        if (! is_string($window) || ! preg_match('/^(\d+)([smh])$/', $window, $matches)) {
            return null;
        }

        $unitSeconds = ['s' => 1, 'm' => 60, 'h' => 3600];

        return (int) $matches[1] * $unitSeconds[$matches[2]];
    }

    /**
     * Execute the ESI job. The $esi client is already authenticated when getRefreshToken() returns a token.
     * At runtime $esi is always a RecordingEsiClient — see EveapiServiceProvider::register().
     */
    abstract public function executeJob(EsiClient $esi): void;

    /**
     * Get the tags that should be assigned to the job.
     */
    abstract public function tags(): array;
}
