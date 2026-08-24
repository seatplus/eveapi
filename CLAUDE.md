# CLAUDE.md — seatplus/eveapi

Guidance for Claude Code working in this package on its own (e.g. opened as a
standalone Orca project). A standalone checkout does **not** inherit the core
app's `CLAUDE.md`, skills, or MCP — this file is the local pointer.

## What this is
**eveapi** — the ESI data layer: queued jobs that fetch EVE data, the Eloquent
models they populate, and Horizon config. It sits in the middle of the one-way
chain `esi-client → eveapi → auth → web` and may import from `esi-client` /
`esi-schema` but **never** from `auth` / `web`.

See **`ARCHITECTURE.md`** for the full job/DB-transaction rationale. The wider
project and the shared `.claude/skills/` live in
**[seatplus/core](https://github.com/seatplus/core)**, whose `CLAUDE.md` is the
source of truth. laravel-boost + the browser MCP are only useful in the assembled
core app, not here.

## ESI job pattern (quick reference)
Jobs extend `EsiJob` and declare a single `OPERATION_CLASS` (an esi-schema
operation class that carries the endpoint metadata). Override `tags()` and
`executeJob(EsiClient $esi)`; the base wraps a DB transaction. Retry model:
`$tries = 0` + `$maxExceptions = 3` + `retryUntil() = now()+30m` — rate-limit
`release()`s are flow control, not failures.

## Testing
Needs PostgreSQL and Redis. The test DB is **`laravel_eveapi`** — a per-package
name so this suite runs in parallel with other packages' suites without
collisions. It's pinned with `force="true"` in `phpunit.xml` so an ambient
`DB_DATABASE` (the dev shell exports `seatplus`) can never redirect a
`migrate:fresh` onto a real database. **Tests must never touch `seatplus`.**
Create it once: `createdb laravel_eveapi`.

```bash
composer run test        # Pint + PHPStan + type-coverage + Pest
vendor/bin/pest --filter "test name"
```

Requires **PHP 8.5** (`require.php` is `^8.5`) and runs Pest 5 / PHPUnit 13.

`composer test:unit` (and so `composer run test`) always executes the whole suite.
Test Impact Analysis is opt-in via **`composer test:unit-tia`**, which replays tests
your change cannot have affected from a cached dependency graph instead of running
them — useful for fast local iteration, ~2s versus ~30s.

⚠️ **Do not use `test:unit-tia` to decide the suite is green.** Recording the graph
needs pcov/Xdebug, but *replaying does not*, so on a stock PHP it silently replays
whatever is cached (in `~/.pest/tia/`, outside the repo, per worktree) and reports
`608 passed … 608 replayed` having executed nothing. Worse, its invalidation only
looks at `.php` source: it hashes `composer.lock`, which this package gitignores (a
gitignored lockfile hashes to null), `composer.json` hashing is disabled upstream,
and its `php_minor` field stores `PHP_MAJOR_VERSION`. So a dependency bump or a
`phpunit.xml` edit does not invalidate the graph at all. That is exactly why it is
not wired into `composer test`.

`tests/Pest.php` forces `pest-plugin-type-coverage` onto pokio's sync runtime. Leave
it: its forked workers corrupt the plugin's shared cache into invalid PHP, and the
corruption is sticky because the plugin `include`s that file. See the comment there.

> See the "Working in Orca" section of core's `CLAUDE.md` for the per-package
> test-DB rationale. Limit: two worktrees of *this* package share `laravel_eveapi`.

## Code style
Spatie PHP guidelines / PSR-12; new PHP files omit the license header (match the
newest sibling files). No `dd()`/`dump()` in committed code.

Every generic return type must declare its type arguments — `@return MorphMany<Contact,
$this>`, not a bare `: MorphMany`. Without them consumers see `Model`, which is what
broke seatplus/web twice. `phpstan.neon.dist` registers `MissingMethodReturnTypehintRule`
on top of level 4 to enforce it; see ARCHITECTURE.md, Decision 11.
