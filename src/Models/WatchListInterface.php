<?php

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

interface WatchListInterface
{
    public function scopeFilterByRegionIds(Builder $query, int|array $regions): Builder;

    public function scopeFilterBySystemIds(Builder $query, int|array $systems): Builder;

    public function scopeFilterByTypeIds(Builder $query, int|array $types): Builder;

    public function scopeFilterByGroupIds(Builder $query, int|array $groups): Builder;

    public function scopeFilterByCategoryIds(Builder $query, int|array $category): Builder;
}
