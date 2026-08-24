<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

/**
 * Marker for models that can be filtered by a type/group/category watchlist.
 *
 * Implementations provide `filterByTypeIds`, `filterByGroupIds` and
 * `filterByCategoryIds` as `protected #[Scope]` methods. They are deliberately
 * not declared here: interface methods must be public, and Larastan only
 * resolves `#[Scope]` methods on the query builder when they are non-public
 * (mirroring Laravel 12's own convention), so declaring them would break static
 * analysis in consuming packages. Nothing calls them through the interface.
 */
interface TypeWatchListInterface {}
