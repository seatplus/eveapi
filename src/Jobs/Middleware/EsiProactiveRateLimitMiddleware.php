<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

/**
 * Proactive rate-limit middleware for ESI queue jobs.
 *
 * ESI uses a floating token bucket per rate-limit group:
 *   X-Ratelimit-Limit:     1800/15m  → capacity 1800, refill 2 tokens/sec
 *   X-Ratelimit-Remaining: 1750      → 1750 tokens left
 *
 * After each successful job response, the job stores remaining/limit/windowSeconds
 * in Redis keyed by group. Before the next job in the same group runs, this
 * middleware checks whether the bucket is low. If so it releases the job
 * rather than burning expensive tokens (4xx = 5 tokens, 2xx = 2 tokens).
 *
 * Token-cost awareness: a 429 costs 5 tokens — far worse than a 2-second delay.
 */
class EsiProactiveRateLimitMiddleware
{
    /** Fraction of capacity below which we throttle (10 %). */
    private const float LOW_THRESHOLD = 0.10;

    /** Minimum error budget remaining before we proactively hold back new jobs. */
    private const int ERROR_LIMIT_THRESHOLD = 10;

    /** Redis key prefix. */
    private const string KEY_PREFIX = 'esi_ratelimit:';

    /** Redis key for error limit state. */
    private const string ERROR_LIMIT_KEY = 'esi_errorlimit:global';

    /** How many seconds to keep the Redis key alive (one full window + buffer). */
    private const int TTL_SECONDS = 1800;

    public function handle(mixed $job, \Closure $next): void
    {
        $group = method_exists($job, 'rateLimitGroup') ? $job->rateLimitGroup() : null;

        if ($group !== null) {
            $charId = method_exists($job, 'rateLimitCharacterId')
                ? (string) ($job->rateLimitCharacterId() ?? 'public')
                : 'public';

            $delay = $this->computeReleaseDelay("{$group}:{$charId}");

            if ($delay > 0) {
                $job->release($delay);

                return;
            }
        }

        $errorDelay = $this->computeErrorLimitDelay();
        if ($errorDelay > 0) {
            $job->release($errorDelay);

            return;
        }

        $next($job);
    }

    /**
     * Store rate-limit state from a completed ESI response.
     *
     * Keyed by (group, characterId) so that different ESI rate-limit buckets
     * are tracked independently. EsiJob::handle() sets the context on
     * RecordingEsiClient before calling executeJob(), so the correct
     * (group, characterId) pair is always available here.
     *
     * The real quota (limit + window) is endpoint-specific and comes from the
     * operation class (RATE_LIMIT_MAX_TOKENS / RATE_LIMIT_WINDOW), threaded through
     * EsiJob::handle() → RecordingEsiClient. It must NOT be hardcoded: e.g. the
     * char-wallet group is 150/15m, so a hardcoded 1800 would read 147 remaining as
     * 8 % (throttle) instead of 98 % (fine). A null limit means the endpoint has no
     * known quota and is never proactively throttled.
     *
     * @param  int|null  $limit  Bucket capacity (RATE_LIMIT_MAX_TOKENS), or null if unknown.
     * @param  int|null  $windowSeconds  Refill window in seconds, or null if unknown.
     * @param  string  $group  ESI rate-limit group (e.g. 'char-wallet', 'characters').
     * @param  string  $characterId  Character ID string, or 'public' for unauthenticated endpoints.
     */
    public static function recordResponse(int $remaining, ?int $limit, ?int $windowSeconds, string $group = 'global', string $characterId = 'public'): void
    {
        Redis::setex(self::KEY_PREFIX."{$group}:{$characterId}", self::TTL_SECONDS, json_encode([
            'remaining' => $remaining,
            'limit' => $limit ?? 0,
            'window_seconds' => $windowSeconds ?? 0,
            'recorded_at' => now()->timestamp,
        ]));
    }

    /**
     * Store error-limit state from an ESI response.
     * X-ESI-Error-Limit-Remain and X-ESI-Error-Limit-Reset are present on all responses.
     */
    public static function recordErrorLimitResponse(int $remaining, int $resetIn): void
    {
        Redis::setex(self::ERROR_LIMIT_KEY, $resetIn + 5, json_encode([
            'remaining' => $remaining,
            'reset_in' => $resetIn,
        ]));
    }

    // -------------------------------------------------------------------------

    private function computeReleaseDelay(string $keySuffix): int
    {
        $raw = Redis::get(self::KEY_PREFIX.$keySuffix);
        if ($raw === null) {
            return 0;
        }

        $state = json_decode($raw, true);
        $remaining = (int) ($state['remaining'] ?? 0);
        $limit = (int) ($state['limit'] ?? 0);
        $windowSeconds = (int) ($state['window_seconds'] ?? 0);
        $recordedAt = (int) ($state['recorded_at'] ?? 0);

        if ($limit === 0 || $windowSeconds === 0) {
            return 0;
        }

        // ESI refills the bucket at limit/window tokens per second. The stored value is
        // a snapshot; age it by the refill accrued since it was recorded. Without this a
        // brief dip below the threshold would starve the group forever — the release
        // prevents the very ESI call that would refresh the snapshot, so it never rises.
        // Integer-first math (multiply before divide) keeps the boundaries exact.
        $elapsed = max(0, now()->timestamp - $recordedAt);
        $refilled = (int) floor(($elapsed * $limit) / $windowSeconds);
        $effectiveRemaining = (int) min($limit, $remaining + $refilled);

        $threshold = (int) ceil(self::LOW_THRESHOLD * $limit);

        if ($effectiveRemaining >= $threshold) {
            return 0;
        }

        // Hold the job just long enough to refill back to the threshold — a single
        // release, rather than re-checking a stale value every second and exhausting
        // the job's retry budget (which is what turned throttling into MaxAttemptsExceeded).
        // delay = deficit / refillRate = deficit * window / limit.
        $deficit = $threshold - $effectiveRemaining;

        return max(1, (int) ceil(($deficit * $windowSeconds) / $limit));
    }

    private function computeErrorLimitDelay(): int
    {
        $raw = Redis::get(self::ERROR_LIMIT_KEY);
        if ($raw === null) {
            return 0;
        }

        $state = json_decode($raw, true);
        $remaining = (int) ($state['remaining'] ?? PHP_INT_MAX);
        $resetIn = (int) ($state['reset_in'] ?? 60);

        if ($remaining >= self::ERROR_LIMIT_THRESHOLD) {
            return 0;
        }

        return $resetIn;
    }
}
