<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

/**
 * Marker for models that can be filtered by a region/system watchlist.
 *
 * Implementations provide `filterByRegionIds` and `filterBySystemIds` as
 * `protected #[Scope]` methods. See {@see TypeWatchListInterface} for why they
 * are not declared on the interface.
 */
interface LocationWatchListInterface {}
