<?php

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
    private const LOW_THRESHOLD = 0.10;

    /** Redis key prefix. */
    private const KEY_PREFIX = 'esi_ratelimit:';

    /** How many seconds to keep the Redis key alive (one full window + buffer). */
    private const TTL_SECONDS = 1800;

    public function handle(mixed $job, \Closure $next): void
    {
        $group = $this->resolveGroup($job);

        if ($group !== null) {
            $delay = $this->computeReleaseDelay($group);

            if ($delay > 0) {
                $job->release($delay);

                return;
            }
        }

        $next($job);
    }

    /**
     * Store rate-limit state from a completed ESI response.
     * Called by EsiBase after a successful retrieve().
     */
    public static function recordResponse(
        string $group,
        int $remaining,
        int $limit,
        int $windowSeconds,
    ): void {
        $key = self::KEY_PREFIX.$group;

        Redis::setex($key, self::TTL_SECONDS, json_encode([
            'remaining' => $remaining,
            'limit' => $limit,
            'window_seconds' => $windowSeconds,
        ]));
    }

    // -------------------------------------------------------------------------

    private function resolveGroup(mixed $job): ?string
    {
        // Jobs expose their rate-limit group via tags(), e.g. ['char-asset', ...]
        // We look for a tag that matches a known ESI rate-limit group pattern.
        if (! method_exists($job, 'tags')) {
            return null;
        }

        foreach ($job->tags() as $tag) {
            $data = Redis::get(self::KEY_PREFIX.$tag);
            if ($data !== null) {
                return $tag;
            }
        }

        return null;
    }

    private function computeReleaseDelay(string $group): int
    {
        $raw = Redis::get(self::KEY_PREFIX.$group);
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
}
