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
     * @param  string  $group  ESI rate-limit group (e.g. 'characters', 'alliances').
     * @param  string  $characterId  Character ID string, or 'public' for unauthenticated endpoints.
     */
    public static function recordResponse(int $remaining, string $group = 'global', string $characterId = 'public'): void
    {
        Redis::setex(self::KEY_PREFIX."{$group}:{$characterId}", self::TTL_SECONDS, json_encode([
            'remaining' => $remaining,
            'limit' => 1800,
            'window_seconds' => 900,
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

        if ($limit === 0 || $windowSeconds === 0) {
            return 0;
        }

        if (($remaining / $limit) >= self::LOW_THRESHOLD) {
            return 0;
        }

        // Calculate delay: refill enough tokens for one request (costs 2 tokens).
        // refill_rate = limit / windowSeconds tokens/sec
        // delay = tokens_needed / refill_rate = 2 / (limit / windowSeconds)
        $refillRate = $limit / $windowSeconds; // tokens per second
        $tokensNeeded = 2; // cost of a 2xx response

        return (int) ceil($tokensNeeded / $refillRate);
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
