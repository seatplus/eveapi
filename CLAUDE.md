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

> See the "Working in Orca" section of core's `CLAUDE.md` for the per-package
> test-DB rationale. Limit: two worktrees of *this* package share `laravel_eveapi`.

## Code style
Spatie PHP guidelines / PSR-12; new PHP files omit the license header (match the
newest sibling files). No `dd()`/`dump()` in committed code.
