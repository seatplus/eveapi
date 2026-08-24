<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

/**
 * Marks a model as filterable by a region/system watchlist, so consumers can
 * branch on `$model instanceof LocationWatchListInterface`.
 *
 * The filter contract itself is {@see FiltersByLocationWatchList}; see
 * {@see TypeWatchListInterface} for why it is not declared here.
 */
interface LocationWatchListInterface {}
