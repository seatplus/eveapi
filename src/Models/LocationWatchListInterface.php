<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

/**
 * Marks a model as filterable by a region/system watchlist, so consumers can
 * dispatch on it — `$model instanceof LocationWatchListInterface`.
 *
 * Implementors must provide `filterByRegionIds` and `filterBySystemIds` as
 * `protected #[Scope]` methods, enforced by
 * `tests/Architecture/WatchListContractTest.php`. See
 * {@see TypeWatchListInterface} for why the obligation cannot live on the interface,
 * and ARCHITECTURE.md, Decision 10.
 */
interface LocationWatchListInterface {}
