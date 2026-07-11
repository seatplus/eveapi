<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
use Seatplus\Eveapi\Jobs\Wallet\CharacterBalanceJob;
use Seatplus\Eveapi\Services\Esi\RecordingEsiClient;

// ---------------------------------------------------------------------------
// RecordingEsiClient — service container binding
// ---------------------------------------------------------------------------

it('RecordingEsiClient is bound as EsiClient in the service container', function (): void {
    expect(app(EsiClient::class))->toBeInstanceOf(RecordingEsiClient::class);
});

it('RecordingEsiClient extends EsiClient', function (): void {
    expect(new ReflectionClass(RecordingEsiClient::class))
        ->and((new ReflectionClass(RecordingEsiClient::class))->getParentClass()->getName())
        ->toBe(EsiClient::class);
});

it('RecordingEsiClient overrides invoke method', function (): void {
    $reflection = new ReflectionClass(RecordingEsiClient::class);
    $method = $reflection->getMethod('invoke');

    expect($method->getDeclaringClass()->getName())->toBe(RecordingEsiClient::class);
});

// ---------------------------------------------------------------------------
// EsiProactiveRateLimitMiddleware — recordResponse + throttle logic
// ---------------------------------------------------------------------------

it('recordResponse writes rate-limit state to Redis keyed by group:charId', function (): void {
    Redis::flushdb();

    EsiProactiveRateLimitMiddleware::recordResponse(1200, 1800, 900, 'characters', '12345678');

    $stored = json_decode((string) Redis::get('esi_ratelimit:characters:12345678'), true);

    expect($stored)->toHaveKey('remaining')
        ->and($stored['remaining'])->toBe(1200)
        ->and($stored['limit'])->toBe(1800);
});

it('recordResponse defaults to global:public when no group/charId given', function (): void {
    Redis::flushdb();

    EsiProactiveRateLimitMiddleware::recordResponse(900, 1800, 900);

    $stored = json_decode((string) Redis::get('esi_ratelimit:global:public'), true);

    expect($stored)->not->toBeNull()
        ->and($stored['remaining'])->toBe(900);
});

it('middleware passes job through when rate-limit is healthy', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordResponse(1800, 1800, 900, 'characters', '12345678');

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public function rateLimitGroup(): string
        {
            return 'characters';
        }

        public function rateLimitCharacterId(): ?int
        {
            return 12345678;
        }

        public function release(int $delay): void {}
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue();
});

it('middleware releases job when rate-limit is critically low', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordResponse(1, 1800, 900, 'characters', '12345678');

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public bool $released = false;

        public function rateLimitGroup(): string
        {
            return 'characters';
        }

        public function rateLimitCharacterId(): ?int
        {
            return 12345678;
        }

        public function release(int $delay): void
        {
            $this->released = true;
        }
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($job->released)->toBeTrue()
        ->and($passed)->toBeFalse();
});

it('middleware passes job through when no rate-limit state exists for that bucket', function (): void {
    Redis::flushdb();

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public function rateLimitGroup(): string
        {
            return 'characters';
        }

        public function rateLimitCharacterId(): ?int
        {
            return 99999999;
        }

        public function release(int $delay): void {}
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue();
});

it('middleware passes job without rateLimitGroup() through (legacy jobs)', function (): void {
    Redis::flushdb();

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public function release(int $delay): void {}
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue();
});

it('middleware uses public as charId when rateLimitCharacterId() returns null', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordResponse(1, 1800, 900, 'alliances', 'public');

    $middleware = new EsiProactiveRateLimitMiddleware;

    $job = new class
    {
        public bool $released = false;

        public function rateLimitGroup(): string
        {
            return 'alliances';
        }

        public function rateLimitCharacterId(): ?int
        {
            return null;
        }

        public function release(int $delay): void
        {
            $this->released = true;
        }
    };

    $middleware->handle($job, function (): void {});

    expect($job->released)->toBeTrue();
});

it('middleware releases job when rate-limit is critically low and job has no rateLimitCharacterId()', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordResponse(1, 1800, 900, 'characters', 'public');

    $middleware = new EsiProactiveRateLimitMiddleware;

    $job = new class
    {
        public bool $released = false;

        public function rateLimitGroup(): string
        {
            return 'characters';
        }

        // intentionally no rateLimitCharacterId() method

        public function release(int $delay): void
        {
            $this->released = true;
        }
    };

    $middleware->handle($job, function (): void {});

    expect($job->released)->toBeTrue();
});

it('computeReleaseDelay returns 0 when Redis state has limit=0', function (): void {
    Redis::flushdb();

    // Write a state with limit=0 — simulates corrupt/missing limit field
    Redis::setex('esi_ratelimit:characters:public', 1800, json_encode([
        'remaining' => 100,
        'limit' => 0,
        'window_seconds' => 900,
    ]));

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public function rateLimitGroup(): string
        {
            return 'characters';
        }

        public function rateLimitCharacterId(): ?int
        {
            return null;
        }

        public function release(int $delay): void {}
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue();
});

it('recordErrorLimitResponse writes error-limit state to Redis', function (): void {
    Redis::flushdb();

    EsiProactiveRateLimitMiddleware::recordErrorLimitResponse(remaining: 45, resetIn: 30);

    $stored = json_decode((string) Redis::get('esi_errorlimit:global'), true);

    expect($stored)->toHaveKey('remaining')
        ->and($stored['remaining'])->toBe(45)
        ->and($stored['reset_in'])->toBe(30);
});

it('middleware passes job through when error budget is healthy', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordErrorLimitResponse(remaining: 50, resetIn: 30);

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public function release(int $delay): void {}
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue();
});

it('middleware releases job for the reset window when error budget is critically low', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordErrorLimitResponse(remaining: 5, resetIn: 42); // below 10 threshold

    $middleware = new EsiProactiveRateLimitMiddleware;
    $released = false;
    $releasedDelay = 0;

    $job = new class
    {
        public bool $released = false;

        public int $releasedDelay = 0;

        public function release(int $delay): void
        {
            $this->released = true;
            $this->releasedDelay = $delay;
        }
    };

    $middleware->handle($job, function (): void {});

    expect($job->released)->toBeTrue()
        ->and($job->releasedDelay)->toBe(42);
});

// ---------------------------------------------------------------------------
// Real endpoint quota — regression for the hardcoded-1800 bug
// ---------------------------------------------------------------------------

it('does not throttle when remaining is healthy against the real (small) endpoint limit', function (): void {
    Redis::flushdb();

    // char-wallet is 150/15m. 147 remaining is 98% — healthy. The old code hardcoded
    // limit=1800, turning this into 8% and throttling the job to MaxAttemptsExceeded.
    EsiProactiveRateLimitMiddleware::recordResponse(147, 150, 900, 'char-wallet', '95725047');

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public bool $released = false;

        public function rateLimitGroup(): string
        {
            return 'char-wallet';
        }

        public function rateLimitCharacterId(): ?int
        {
            return 95725047;
        }

        public function release(int $delay): void
        {
            $this->released = true;
        }
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue()
        ->and($job->released)->toBeFalse();
});

it('ages the stored snapshot by refill so a genuine dip self-heals after one release', function (): void {
    Redis::flushdb();
    Carbon::setTestNow(now());

    // Genuinely low: 5/150 = 3.3% (threshold is 15). Refill = 150/900 = 1 token / 6s.
    EsiProactiveRateLimitMiddleware::recordResponse(5, 150, 900, 'char-wallet', '95725047');

    $makeJob = fn () => new class
    {
        public bool $released = false;

        public int $releasedDelay = 0;

        public function rateLimitGroup(): string
        {
            return 'char-wallet';
        }

        public function rateLimitCharacterId(): ?int
        {
            return 95725047;
        }

        public function release(int $delay): void
        {
            $this->released = true;
            $this->releasedDelay = $delay;
        }
    };

    // Immediately: released just long enough to refill from 5 back to the threshold (15).
    $first = $makeJob();
    (new EsiProactiveRateLimitMiddleware)->handle($first, fn () => null);
    expect($first->released)->toBeTrue()
        ->and($first->releasedDelay)->toBe(60); // (15 - 5) tokens / (1/6 per sec) = 60s

    // After that delay the aged snapshot has refilled to the threshold — job passes,
    // instead of re-checking a frozen value and exhausting its retries.
    Carbon::setTestNow(now()->addSeconds(60));
    $second = $makeJob();
    $passed = false;
    (new EsiProactiveRateLimitMiddleware)->handle($second, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue()
        ->and($second->released)->toBeFalse();

    Carbon::setTestNow();
});

it('EsiJob derives the real quota from the operation class constants', function (): void {
    $job = new CharacterBalanceJob(95725047);

    // GetCharactersCharacterIdWallet: RATE_LIMIT_GROUP=char-wallet, MAX_TOKENS=150, WINDOW=15m
    expect($job->rateLimitGroup())->toBe('char-wallet')
        ->and($job->rateLimitMaxTokens())->toBe(150)
        ->and($job->rateLimitWindowSeconds())->toBe(900);
});
