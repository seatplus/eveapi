<?php

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

interface LocationWatchListInterface
{
    public function scopeFilterByRegionIds(Builder $query, int|array $regions): Builder;

    public function scopeFilterBySystemIds(Builder $query, int|array $systems): Builder;
}
