# Architecture & Design Decisions — seatplus/eveapi

This document records the key design decisions made for `seatplus/eveapi`, with context, rationale, alternatives considered, and consequences. It is intended for contributors and for AI agents working in this repository.

---

## Decision 1 — `EsiJob` as the single abstract base for all ESI leaf jobs

### Context
Earlier iterations had ad-hoc job classes each implementing their own HTTP calls, token refreshing, error handling, and retry logic. Every job duplicated the same boilerplate. Changing how rate-limiting worked meant touching every job class.

### Decision
All ESI leaf jobs extend `EsiJob`. Leaf jobs implement exactly three things:

1. `protected const string OPERATION_CLASS` — which esi-schema resource class to call
2. `public function tags(): array` — unique identity and Horizon display label
3. `public function executeJob(EsiClient $esi): void` — the actual DB write logic

For authenticated endpoints, they additionally override:

4. `public function getRefreshToken(): RefreshToken` — returns the character's token

Everything else — auth, token refresh, rate-limit middleware, exception handling, DB transaction wrapping — lives in `EsiJob::handle()` and is inherited.

```php
// Minimal public job:
final class CharacterInfoJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterId::class;

    public function __construct(public int $character_id) {}

    public function tags(): array
    {
        return ['character', 'info', "character_id:{$this->character_id}"];
    }

    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->character_id);
        if ($response->isCachedLoad) {
            return;
        }
        CharacterInfo::updateOrCreate(['character_id' => $this->character_id], [...]);
    }
}

// Authenticated job additionally overrides:
public function getRefreshToken(): RefreshToken
{
    return RefreshToken::findOrFail($this->character_id);
}
```

### Rationale
- **Single place to change cross-cutting concerns** — rate-limiting, retry policy, error-limit handling all live in one class.
- **Leaf jobs stay small** — the only code in a leaf job is the domain logic: which ESI endpoint to call and how to persist the result.
- **`ShouldBeUnique` via `tags()`** — `uniqueId()` is derived from `implode(', ', $this->tags())`, so uniqueness is declared once and reused.

### Alternatives considered
- **Interface-based traits** (`HasPathValuesInterface`, `HasRequiredScopeInterface`, `HasQueryParametersInterface`, `HasRequestBodyInterface`) — this was the previous pattern. Each job mixed in traits that described its ESI call shape. Replaced because it spread concerns across traits, made PHPStan unhappy, and duplicated middleware setup in every job.
- **Keeping ad-hoc jobs** — rejected: impossible to add a feature (e.g. proactive rate-limit guard) without touching ~30 job classes.

### Consequences
- `BaseJobInterface::executeJob(): void` still exists but is superseded by `EsiJob::executeJob(EsiClient $esi): void`. The interface is kept for backward compatibility but all current jobs extend `EsiJob`.
- The `EsiJob::handle()` signature is injected by the container: `handle(EsiClient $esi, GetUpToDateRefreshTokenService $tokenService, InvalidTokenThrottleService $throttle)`.
- 10 tries with exponential backoff (1m, 5m, 10m, then 15m × 6).

---

## Decision 2 — `OPERATION_CLASS` constant pattern (esi-schema static classes replace old HTTP traits)

### Context
The old pattern used constructor arguments (`method`, `endpoint`, `version`) to define the ESI call, combined with `HasPathValues`, `HasRequiredScope`, etc. traits. Metadata like rate-limit group or required scope had to be declared separately per job.

### Decision
Each job declares `protected const string OPERATION_CLASS = SomeEsiSchemaClass::class`. The esi-schema class (`seatplus/esi-schema`) is a generated static class for a single ESI endpoint:

```php
// From seatplus/esi-schema:
final class GetCharactersCharacterId
{
    public const ?string REQUIRED_SCOPE        = null;           // public endpoint
    public const ?string RATE_LIMIT_GROUP      = 'char-info';
    public const ?int    RATE_LIMIT_MAX_TOKENS = 1800;
    public const ?string RATE_LIMIT_WINDOW     = '15m';
    public const ?int    CACHE_AGE             = 3600;
    public const array   REQUIRED_ROLES        = [];
    public const bool    USES_CURSOR           = false;

    public static function execute(EsiTransportInterface $transport, int $characterId): EsiResult { ... }
}
```

The job calls it statically: `self::OPERATION_CLASS::execute($esi, $this->character_id)`.

`EsiJob` derives the rate-limit group automatically:
```php
public function rateLimitGroup(): string
{
    $op = static::OPERATION_CLASS;
    $group = defined("{$op}::RATE_LIMIT_GROUP") ? constant("{$op}::RATE_LIMIT_GROUP") : null;
    return is_string($group) && $group !== '' ? $group : 'global';
}
```

### Rationale
- **Zero duplication of endpoint metadata** — scope, rate-limit group, cache TTL are all in one generated class; the job just references it.
- **PHPStan sees return types** — `execute()` returns a typed `EsiResult`; property accesses like `$response->name` are statically known.
- **Adding a new endpoint** means creating a new job that references a new esi-schema class — no framework code changes.

### Alternatives considered
- **Keeping HTTP traits** — `HasPathValues`, `HasRequiredScope`, etc. were cleaner than raw constructor strings but still required each job to declare scope and endpoint shape independently. Rate-limit group was always manual.
- **Annotating jobs with attributes** — possible but requires runtime reflection at dispatch time; rejected in favour of compile-time constants.

### Consequences
- `seatplus/esi-schema` is a required dependency (pulled transitively; `^1.3` at time of writing).
- `EsiResult::$isCachedLoad` — if `true`, the ESI response came from RFC 7234 cache; `executeJob` should return early without writing to the DB.
- `EsiResult::$pages` — total pages for paginated endpoints; jobs loop `$page = 1; do { ... } while ($page++ < $response->pages)`.

---

## Decision 3 — `RecordingEsiClient` as a transparent decorator

### Context
`EsiJob::handle()` receives an `EsiClient` instance from the Laravel container. After each ESI call, the job needs to record `X-Ratelimit-Remaining` and `X-ESI-Error-Limit-Remain` into Redis so `EsiProactiveRateLimitMiddleware` can act on them before the next job runs. But leaf jobs must not know about rate-limit recording.

### Decision
`RecordingEsiClient` extends `EsiClient` and overrides `invoke()`:

```php
class RecordingEsiClient extends EsiClient
{
    public function setContext(string $group, ?int $characterId): void { ... }

    public function invoke(...): EsiRawResponse
    {
        $response = parent::invoke(...);
        EsiProactiveRateLimitMiddleware::recordResponse($response->rateLimitRemaining, $group, $charId);
        EsiProactiveRateLimitMiddleware::recordErrorLimitResponse($response->errorLimitRemaining, ...);
        return $response;
    }
}
```

`EveapiServiceProvider::register()` binds it:
```php
$this->app->bind(EsiClient::class, RecordingEsiClient::class);
```

`EsiJob::handle()` calls `$esi->setContext($this->rateLimitGroup(), $this->rateLimitCharacterId())` before `executeJob()`, so the correct `(group, characterId)` key is always in scope during the job's ESI calls.

### Rationale
- **Zero changes to leaf jobs** — they are type-hinted `EsiClient`; the container transparently injects `RecordingEsiClient`.
- **Decorator, not monkey-patch** — the parent's HTTP logic is unchanged; only the post-response recording is added.
- **Context set once per job** — `setContext()` is called in `EsiJob::handle()` before the abstract `executeJob()`, so all `invoke()` calls within a single job execution share the correct rate-limit key.

### Alternatives considered
- **Recording inside `EsiJob::handle()` by wrapping `executeJob()`** — requires inspecting the response object after the fact; not possible when `executeJob()` makes multiple paginated calls.
- **Static recording in esi-client itself** — would couple esi-client to a Redis dependency it doesn't need.

### Consequences
- In tests: `RecordingEsiClient` is not used; tests bind a mock `EsiClient` directly.
- `rateLimitGroup()` falls back to `'global'` if `OPERATION_CLASS` is unset or has no `RATE_LIMIT_GROUP` constant.
- `rateLimitCharacterId()` returns `null` for public (unauthenticated) endpoints; stored as `'public'` in Redis.

---

## Decision 4 — Two-tier rate limiting: proactive bucket-guard + reactive exception throttle

### Context
ESI uses a floating token bucket per rate-limit group (`X-Ratelimit-Limit: 1800/15m`). A 429 response costs 5 tokens; a 2xx costs 2 tokens. Burning the bucket with aggressive retries is worse than a brief delay.

### Decision
Two middleware layers run on every `EsiJob`:

**Tier 1 — `EsiProactiveRateLimitMiddleware`** (runs before the job executes):
- Reads `esi_ratelimit:{group}:{characterId}` from Redis.
- If `remaining / limit < 10%`, releases the job for `ceil(2 / refill_rate)` seconds.
- Also reads `esi_errorlimit:global`; if `errorLimitRemaining < 10`, releases for `resetIn` seconds.

**Tier 2 — `ThrottlesExceptionsWithRedis(80, 5*60)`** (runs after, reactive):
- On a thrown exception, backs off and retries. Keyed by `'esiratelimit'` globally.

```php
public function middleware(): array
{
    return [
        new EsiProactiveRateLimitMiddleware,
        (new ThrottlesExceptionsWithRedis(80, 5 * 60))->by('esiratelimit')->backoff(5),
    ];
}
```

### Rationale
- **Proactive is cheaper** — a 2-second `release()` costs nothing; a 429 deducts 5 tokens and triggers ESI's error-limit window.
- **Per-(group, characterId) key** — different characters on different rate-limit buckets don't block each other.
- **Error limit is global** — `X-ESI-Error-Limit-Remain` applies across all endpoints; tracked with a single Redis key.

### Alternatives considered
- **Only reactive throttling** — simpler, but burns tokens unnecessarily. Rejected after observing ESI 429 storms in production.
- **Laravel `RateLimited` middleware** — does not understand ESI's floating bucket model; cannot proactively check remaining tokens.

### Consequences
- `CharacterBatchJob` and `UpdateCorporation` have their own `RateLimitedWithRedis` middleware (1 batch per hour per character/corporation) — this is separate from the per-endpoint `EsiJob` rate limiting.
- High-priority `CharacterBatchJob` dispatches (`queue = 'high'`) bypass the `character_batch` rate limiter via `Limit::none()`.

---

## Decision 5 — Distributed token-refresh lock + invalid-token throttle/auto-delete

### Context
Multiple queued jobs can start concurrently for the same character. If the access token is expired, all jobs will try to refresh it simultaneously, causing a token-rotation race where all but one refresh call succeed — invalidating the others' new tokens.

### Decision
`GetUpToDateRefreshTokenService::get()` wraps the refresh in a named Redis lock:

```php
Cache::lock("get up to date refresh_token of character_id: {$character_id}", 10)
    ->block(30, function () use ($refresh_token) {
        $token = $refresh_token->refresh();
        if (carbon($token->expires_on)->gt(now()->addMinute())) {
            return $token;   // already valid, skip refresh
        }
        return $this->updateRefreshTokenService->update($token);
    });
```

If the token refresh returns HTTP 400 or 401 (token revoked/invalid), an `InvalidRefreshTokenException` is thrown. `EsiJob::handle()` catches it and calls `InvalidTokenThrottleService::hit($characterId)`. After 5 consecutive failures within 5 minutes, the `RefreshToken` record is deleted and the job is permanently failed.

### Rationale
- **Lock serialises refreshes** — only one worker refreshes per character at a time; others block up to 30s then reuse the new token.
- **Auto-delete on repeated failure** — a permanently revoked token should not block the queue indefinitely. 5 failures / 5 min is a conservative threshold that avoids accidental deletion on transient ESI errors.
- **400/401 only** — other HTTP errors (500, 503) are rethrown as-is and handled by `ThrottlesExceptionsWithRedis`.

### Alternatives considered
- **No locking, accept occasional duplicate refresh** — EVE's OAuth server handles this gracefully for rotating tokens, but it wastes one refresh call and briefly invalidates a new token that another job just obtained.
- **Delete on first failure** — too aggressive; ESI sometimes returns transient 401s.

### Consequences
- The lock TTL is 10 seconds; `block(30)` means up to 30 seconds waiting. Jobs with a 120-second timeout are unaffected.
- If the lock cannot be acquired within 30 seconds, a `LockTimeoutException` propagates and the job retries via `ThrottlesExceptionsWithRedis`.

---

## Decision 6 — `CharacterBatchJob` self-rescheduling with `ShouldBeUnique` guard

### Context
Character data has varying freshness requirements. Some endpoints (assets) change infrequently; others (skill queue) change continuously. A fixed cron interval either over-fetches or under-fetches.

### Decision
`CharacterBatchJob` is `ShouldBeUnique` keyed by `(character_id, queue)`. When `$reschedule = true`, the batch's `finally` callback re-dispatches the same job with a `REFRESH_DELAY_MINUTES` (5 minutes) delay:

```php
->finally(function (Batch $batch) use ($character_id, $queue, $reschedule) {
    BatchUpdate::where('batch_id', $batch->id)->update(['finished_at' => now()]);
    if ($reschedule) {
        CharacterBatchJob::dispatch($character_id, $queue, reschedule: true)
            ->delay(now()->addMinutes(self::REFRESH_DELAY_MINUTES));
    }
})
```

`BatchUpdate` tracks per-character batch state (`started_at` / `finished_at` / `batch_id`). Before dispatching a new batch, `shouldDiscardUpdate()` checks `is_pending` — if a batch is already in-flight for this character, the job exits early.

`UpdateCharacter` (the bootstrapper) dispatches `CharacterBatchJob` for characters that either never ran or are stale (last `finished_at` > 2× `REFRESH_DELAY_MINUTES` ago).

### Rationale
- **`ShouldBeUnique` prevents queue pile-up** — if two events trigger a batch for the same character simultaneously, only one enters the queue.
- **Self-rescheduling decouples cadence from cron** — the job reschedules itself based on its own completion time, not a fixed clock interval.
- **`finally` not `then`** — the reschedule fires even when some sub-jobs fail (`allowFailures()`), ensuring continuous character updates.

### Alternatives considered
- **Fixed cron + `ShouldBeUnique`** — simpler but ties refresh cadence to cron tick granularity.
- **Laravel scheduler per character** — not feasible for thousands of characters; one DB-driven schedule entry per character would bloat the schedules table.

### Consequences
- High-priority batches (triggered by `ReactOnFreshRefreshToken` or manual refresh) use `queue = 'high'` and bypass the `character_batch` rate limiter.
- `BatchStatistic` records duration for each batch; visible in the Horizon dashboard.
- `queue:prune-batches` runs daily to keep the `job_batches` table clean.

---

## Decision 7 — DB-driven `Schedules` table for recurring jobs

### Context
Some jobs need to run on a fixed cron schedule (SDE import weekly, character affiliations every 5 minutes). Hard-coding these in `EveapiServiceProvider` makes it impossible to change cadence without a code deploy.

### Decision
A `Schedules` Eloquent model stores `(job, expression)` pairs. `EveapiServiceProvider::addSchedules()` reads all rows and registers them with the Laravel scheduler:

```php
Schedules::cursor()->each(function (Schedules $entry) use ($schedule) {
    if (class_exists($entry->job)) {
        $schedule->job(new $entry->job)->cron($entry->expression);
    }
});
```

Migrations seed the initial entries (e.g. `SdeImportJob` weekly). The `seatplus:sde-import` command can also be run ad-hoc or pointed at a local zip file.

A small number of schedules that must never be disabled are still hardcoded (e.g. `RefreshCharacterAffiliationsService` every 5 minutes, `horizon:snapshot` every 5 minutes, `horizon:terminate` hourly).

### Rationale
- **Operator-adjustable without code changes** — a site admin can change the SDE import window by updating a DB row.
- **Safe bootstrap** — the scheduler wraps the DB read in `try/catch`; if the `schedules` table doesn't exist yet (fresh install before migrations), it silently skips.

### Alternatives considered
- **All schedules hardcoded in the service provider** — simpler, but requires a deploy to change any schedule.
- **Config file** — config is static; doesn't allow runtime changes without a config:cache refresh.

### Consequences
- Jobs referenced in the `schedules` table must be resolvable classes. `class_exists($entry->job)` guards against orphaned rows after package updates.

---

## Decision 8 — Event-driven first-fetch on `RefreshTokenCreated`

### Context
When a user authenticates via EVE SSO for the first time, or adds a new character, their character data is not yet in the database. Waiting for the next scheduled `UpdateCharacter` pass could take up to `REFRESH_DELAY_MINUTES` minutes.

### Decision
`EveapiServiceProvider` listens for `RefreshTokenCreated`:

```php
app('events')->listen(RefreshTokenCreated::class, ReactOnFreshRefreshToken::class);
```

`ReactOnFreshRefreshToken::handle()` immediately dispatches `UpdateCharacter` on the `high` queue, passing the new token:

```php
UpdateCharacter::dispatch($refresh_token)->onQueue('high');
```

`UpdateCharacter::updateSingleCharacter()` then dispatches a `CharacterBatchJob` on `high` for that specific character.

### Rationale
- **Instant first-load** — the character's data is available within seconds of login rather than minutes.
- **`high` queue** — ensures this flush doesn't queue behind hundreds of scheduled background updates.
- **Decoupled** — the auth layer (which fires the event) doesn't need to know about `CharacterBatchJob`.

### Alternatives considered
- **Dispatch in `CallbackController`** — couples the HTTP layer to the job dispatch. Using an event keeps the auth package ignorant of eveapi internals.
- **Polling from the UI** — poor UX; requires frontend changes.

### Consequences
- `RefreshTokenCreated` is fired by `ReactOnFreshRefreshToken` in the auth package's `UpdateRefreshTokenAction`. The auth package depends on eveapi, so it fires the event; eveapi listens.
- High-priority `CharacterBatchJob` skips the `character_batch` rate limiter (`Limit::none()` when `queue = 'high'`).

---

## Decision 9 — User-Agent chaining via `EsiConfiguration` singleton

### Context
CCP requires EVE application User-Agents to include a contact email or URL. `seatplus/esi-client` sets the base User-Agent in `EsiConfiguration`. As `seatplus/eveapi` sits on top, it should append its own identity without overwriting esi-client's string.

### Decision
`EveapiServiceProvider::boot()` appends to the singleton:

```php
$version = InstalledVersions::getPrettyVersion('seatplus/eveapi') ?? 'dev';
EsiConfiguration::getInstance()->http_user_agent .= " seatplus/eveapi/{$version} +https://github.com/seatplus/eveapi";
```

The final User-Agent on outbound ESI requests becomes:
```
seatplus/esi-client/{version} seatplus/eveapi/{version} +https://github.com/seatplus/eveapi
```

Higher-layer packages (`auth`, `web`) can append further if needed.

### Rationale
- **CCP compliance** — ESI Terms of Service require a meaningful User-Agent; the URL provides a point of contact.
- **Layered identity** — each package in the stack appends its own segment; the full string shows exactly which package combination is in use.
- **Singleton mutation** — `EsiConfiguration` is a singleton; mutating it in `boot()` affects all subsequent Guzzle requests without requiring any changes to esi-client.

### Alternatives considered
- **Config value** — would require app-level config changes on every version bump; not suitable for a library.
- **Override entirely in eveapi** — would lose esi-client's identity from the User-Agent string.

### Consequences
- Boot order matters: esi-client's service provider must boot before eveapi's. Laravel's package auto-discovery handles this correctly.
- `InstalledVersions::getPrettyVersion()` returns `null` in local dev (monorepo path-source install); falls back to `'dev'`.
