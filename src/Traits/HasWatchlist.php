<?php

namespace Seatplus\Eveapi\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasWatchlist
{
    abstract public function scopeInRegion(Builder $query, int | array $regions): Builder;

    abstract public function scopeInSystems(Builder $query, int | array $systems): Builder;

    abstract public function scopeOfTypes(Builder $query, int | array $types) : Builder;

    abstract public function scopeOfGroups(Builder $query, int | array $groups) : Builder;

    abstract public function scopeOfCategories(Builder $query, int | array $categories) : Builder;
}
