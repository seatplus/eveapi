<?php

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

interface TypeWatchListInterface
{
    public function scopeFilterByTypeIds(Builder $query, int|array $types): Builder;

    public function scopeFilterByGroupIds(Builder $query, int|array $groups): Builder;

    public function scopeFilterByCategoryIds(Builder $query, int|array $category): Builder;
}
