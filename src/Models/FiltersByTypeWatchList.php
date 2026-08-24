<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * The type/group/category watchlist contract, paired with
 * {@see TypeWatchListInterface}.
 *
 * These declarations live here rather than on the interface because interface
 * methods must be public, and Larastan resolves `#[Scope]` methods on the query
 * builder only when they are non-public (`larastan/larastan`,
 * `src/Methods/BuilderHelper.php` — the Laravel 12 convention it enforces via its
 * own `NoPublicModelScopeAndAccessorRule`). A public declaration therefore makes
 * `Model::query()->filterByTypeIds(…)` unresolvable in every consuming package.
 *
 * `abstract protected` gets the enforcement back: PHP fails at class load if an
 * implementation is missing or its signature drifts, while keeping the scopes in
 * the shape consumers can actually analyse. Implementations must also be
 * non-public and carry `#[Scope]` — the two things PHP cannot enforce here, and
 * so asserted by `tests/Architecture/WatchListContractTest.php`.
 */
trait FiltersByTypeWatchList
{
    abstract protected function filterByTypeIds(Builder $query, int|array $types): Builder;

    abstract protected function filterByGroupIds(Builder $query, int|array $groups): Builder;

    abstract protected function filterByCategoryIds(Builder $query, int|array $category): Builder;
}
