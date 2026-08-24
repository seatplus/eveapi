<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

/**
 * Marks a model as filterable by a type/group/category watchlist, so consumers can
 * dispatch on it — `$model instanceof TypeWatchListInterface`, or narrowing a
 * `Builder|TypeWatchListInterface` union.
 *
 * Implementors must provide `filterByTypeIds`, `filterByGroupIds` and
 * `filterByCategoryIds` as `protected #[Scope]` methods. That obligation cannot be
 * declared here, and deliberately is not:
 *
 * - PHP rejects non-public interface methods outright ("Access type for interface
 *   method must be public").
 * - Larastan resolves `#[Scope]` methods onto the query builder only when they are
 *   *non-public* (`larastan/larastan`, `src/Methods/BuilderHelper.php`).
 *
 * So declaring them would force the one shape that makes
 * `Model::query()->filterByTypeIds(…)` unresolvable in every consuming package — the
 * regression this interface's public methods used to cause. The obligation is
 * enforced instead by `tests/Architecture/WatchListContractTest.php`, which discovers
 * every implementor and asserts each method exists, stays non-public and keeps
 * `#[Scope]`. See ARCHITECTURE.md, Decision 10.
 */
interface TypeWatchListInterface {}
