<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * The region/system watchlist contract, paired with
 * {@see LocationWatchListInterface}.
 *
 * See {@see FiltersByTypeWatchList} for why these declarations are
 * `abstract protected` on a trait rather than public on the interface.
 */
trait FiltersByLocationWatchList
{
    abstract protected function filterByRegionIds(Builder $query, int|array $regions): Builder;

    abstract protected function filterBySystemIds(Builder $query, int|array $systems): Builder;
}
