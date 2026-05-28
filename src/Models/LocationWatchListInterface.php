<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

interface LocationWatchListInterface
{
    public function filterByRegionIds(Builder $query, int|array $regions): Builder;

    public function filterBySystemIds(Builder $query, int|array $systems): Builder;
}
