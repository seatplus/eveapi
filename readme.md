# seatplus/eveapi

[![Latest Version on Packagist](https://img.shields.io/packagist/v/seatplus/eveapi.svg?style=flat-square)](https://packagist.org/packages/seatplus/eveapi)
[![Tests](https://github.com/seatplus/eveapi/actions/workflows/tests.yml/badge.svg?branch=4.x)](https://github.com/seatplus/eveapi/actions/workflows/tests.yml)
[![Formats & Static Analysis](https://github.com/seatplus/eveapi/actions/workflows/formats.yml/badge.svg?branch=4.x)](https://github.com/seatplus/eveapi/actions/workflows/formats.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/seatplus/eveapi.svg?style=flat-square)](https://packagist.org/packages/seatplus/eveapi)
[![License](https://img.shields.io/packagist/l/seatplus/eveapi.svg?style=flat-square)](https://packagist.org/packages/seatplus/eveapi)
[![PHP](https://img.shields.io/packagist/dependency-v/seatplus/eveapi/php.svg?style=flat-square)](https://packagist.org/packages/seatplus/eveapi)

The EVE Online data-fetching layer for the seatplus platform. Provides queued ESI jobs, Eloquent models for EVE entities, Laravel Horizon integration, SDE import, and reactive character scheduling.

---

## Architecture

`eveapi` is the second tier of the four-package seatplus monorepo. It has a strict one-way dependency hierarchy:

```
esi-client   (standalone Guzzle HTTP client, RFC 7234 caching)
     ↓
eveapi       ← YOU ARE HERE
     ↓
auth         (EVE OAuth, role system, SSO compliance)
     ↓
web          (Vue 3 + Inertia.js frontend — optional)
```

Lower packages never import from higher packages.

### ESI Data Flow

```
Queued ESI Job (EsiJob subclass)
       │
       ▼ handle() — injected by container
EsiJob — refreshes token, sets rate-limit context
       │ EsiClient (RecordingEsiClient in production)
       ▼
OPERATION_CLASS::execute($esi, ...) — esi-schema static call
       │ EsiResult (typed DTO, isCachedLoad flag)
       ▼
executeJob() — DB upsert inside a DB::transaction
```

Recording happens transparently: `RecordingEsiClient` wraps every `invoke()` call and stores `X-Ratelimit-Remaining` + `X-ESI-Error-Limit-Remain` in Redis for the proactive rate-limit guard.

For full architecture decisions see [ARCHITECTURE.md](ARCHITECTURE.md).

---

## Requirements

| Dependency | Version |
|-----------|---------|
| PHP | ^8.3 |
| Laravel | ^13.0 |
| PostgreSQL | 17+ (tests), any for production |
| Redis | 7+ (required for Horizon and rate-limit tracking) |
| seatplus/esi-client | ^4.1 |
| seatplus/esi-schema | ^1.3 (transitive) |

---

## Installation

```bash
composer require seatplus/eveapi
```

Publish and run the migrations:

```bash
php artisan migrate
```

---

## Features

### ESI Queue Jobs

All ESI leaf jobs extend `EsiJob` (`ShouldQueue` + `ShouldBeUnique`, 10 tries, exponential backoff). Each job declares the esi-schema resource class it calls via `OPERATION_CLASS`, and implements `tags()` and `executeJob()`. For authenticated endpoints, override `getRefreshToken()`.

**Public endpoint example:**

```php
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

        CharacterInfo::updateOrCreate(
            ['character_id' => $this->character_id],
            ['name' => $response->name, ...],
        );
    }
}
```

**Authenticated endpoint** — additionally override `getRefreshToken()`:

```php
public function getRefreshToken(): RefreshToken
{
    return RefreshToken::findOrFail($this->character_id);
}
```

**Paginated endpoint** — loop manually using `$response->pages`:

```php
$page = 1;
do {
    $response = self::OPERATION_CLASS::execute($esi, $this->character_id, $page);
    if ($response->isCachedLoad) { return; }
    // ... collect $response->data ...
    $page++;
} while ($page <= $response->pages);
```

### Eloquent Models for EVE Data

| Entity | Models |
|--------|--------|
| Character | `CharacterInfo`, `CharacterAffiliation`, `CharacterRole`, `CorporationHistory` |
| Corporation | `CorporationInfo`, `CorporationMemberTracking`, `CorporationDivision` |
| Alliance | `AllianceInfo` |
| Assets | `Asset` |
| Mail | `Mail`, `MailRecipients` |
| Skills | `Skill`, `SkillQueue` |
| Other | `RefreshToken`, `SsoScopes`, `Schedules`, `BatchUpdate`, `BatchStatistic` |

### Reactive Character Scheduling

`CharacterBatchJob` self-reschedules character data refreshes based on ESI `Cache-Control` / `Expires` headers. When a character's refresh window opens, the job re-queues itself automatically — no cron polling required.

### ESI Rate-Limit Tracking

Per-`(group, characterId)` bucket tracking prevents hitting ESI error limits. Jobs back off transparently when a bucket is close to the limit.

### SDE Import

Imports the EVE Static Data Export (universe types, groups, categories, regions, constellations, and solar systems) directly from CCP's FTP:

```bash
php artisan seatplus:sde-import

# Or from a local zip (skips download):
php artisan seatplus:sde-import --source=/path/to/sde.zip
```

The SDE import is also scheduled to run automatically every week.

### EVE-Compliant User-Agent

On boot, `EveapiServiceProvider` sets the EVE-compliant `User-Agent` header (sourced from `seatplus/esi-client`'s `EsiConfiguration`) on all outbound Guzzle requests.

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `seatplus:sde-import` | Download and import the EVE SDE |
| `seatplus:check:endpoints` | Verify configured ESI endpoints are reachable |
| `seatplus:cache:clear [--force]` | Clear the ESI HTTP response cache |

---

## Testing

Tests require a running PostgreSQL instance (`seatplus`/`secret` @ `127.0.0.1:5432`) and Redis (`127.0.0.1:6379`).

```bash
cd packages/eveapi

composer run test              # Full suite: lint + types + type-coverage + unit tests
composer run test:unit         # Pest unit tests only
composer run test:lint         # Pint formatting check
composer run lint              # Auto-fix formatting
composer run test:types        # PHPStan / Larastan static analysis
composer run test:type-coverage # 100% type coverage (enforced)
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## License

MIT. Please see [LICENSE](LICENSE.md) for details.

