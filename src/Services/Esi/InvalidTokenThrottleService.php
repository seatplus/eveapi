<?php

namespace Seatplus\Eveapi\Services\Esi;

use Illuminate\Support\Facades\RateLimiter;

class InvalidTokenThrottleService
{
    public const int MAX_FAILURES = 5;

    public const int DECAY_SECONDS = 300;

    public function hit(int $characterId): bool
    {
        $key = "invalid_token:{$characterId}";

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return RateLimiter::tooManyAttempts($key, self::MAX_FAILURES);
    }
}
