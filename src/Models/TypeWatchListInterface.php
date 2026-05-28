<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

use Illuminate\Database\Eloquent\Builder;

interface TypeWatchListInterface
{
    public function filterByTypeIds(Builder $query, int|array $types): Builder;

    public function filterByGroupIds(Builder $query, int|array $groups): Builder;

    public function filterByCategoryIds(Builder $query, int|array $category): Builder;
}
