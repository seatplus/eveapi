<?php

use Illuminate\Support\Facades\Redis;
use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\Middleware\EsiProactiveRateLimitMiddleware;
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

    EsiProactiveRateLimitMiddleware::recordResponse(1200, 'characters', '12345678');

    $stored = json_decode(Redis::get('esi_ratelimit:characters:12345678'), true);

    expect($stored)->toHaveKey('remaining')
        ->and($stored['remaining'])->toBe(1200)
        ->and($stored['limit'])->toBe(1800);
});

it('recordResponse defaults to global:public when no group/charId given', function (): void {
    Redis::flushdb();

    EsiProactiveRateLimitMiddleware::recordResponse(900);

    $stored = json_decode(Redis::get('esi_ratelimit:global:public'), true);

    expect($stored)->not->toBeNull()
        ->and($stored['remaining'])->toBe(900);
});

it('middleware passes job through when rate-limit is healthy', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordResponse(1800, 'characters', '12345678');

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public function rateLimitGroup(): string { return 'characters'; }

        public function rateLimitCharacterId(): ?int { return 12345678; }

        public function release(int $delay): void {}
    };

    $middleware->handle($job, function () use (&$passed): void {
        $passed = true;
    });

    expect($passed)->toBeTrue();
});

it('middleware releases job when rate-limit is critically low', function (): void {
    Redis::flushdb();
    EsiProactiveRateLimitMiddleware::recordResponse(1, 'characters', '12345678');

    $middleware = new EsiProactiveRateLimitMiddleware;
    $passed = false;

    $job = new class
    {
        public bool $released = false;

        public function rateLimitGroup(): string { return 'characters'; }

        public function rateLimitCharacterId(): ?int { return 12345678; }

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
        public function rateLimitGroup(): string { return 'characters'; }

        public function rateLimitCharacterId(): ?int { return 99999999; }

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
    EsiProactiveRateLimitMiddleware::recordResponse(1, 'alliances', 'public');

    $middleware = new EsiProactiveRateLimitMiddleware;

    $job = new class
    {
        public bool $released = false;

        public function rateLimitGroup(): string { return 'alliances'; }

        public function rateLimitCharacterId(): ?int { return null; }

        public function release(int $delay): void
        {
            $this->released = true;
        }
    };

    $middleware->handle($job, function (): void {});

    expect($job->released)->toBeTrue();
});

// ---------------------------------------------------------------------------
// EsiProactiveRateLimitMiddleware — error limit tracking
// ---------------------------------------------------------------------------

it('recordErrorLimitResponse writes error-limit state to Redis', function (): void {
    Redis::flushdb();

    EsiProactiveRateLimitMiddleware::recordErrorLimitResponse(remaining: 45, resetIn: 30);

    $stored = json_decode(Redis::get('esi_errorlimit:global'), true);

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
