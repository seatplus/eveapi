<?php

declare(strict_types=1);

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Models\Universe;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Assets\Asset;
use Seatplus\Eveapi\Models\LocationWatchListInterface;

#[Unguarded] #[WithoutIncrementing]

class Location extends Model implements LocationWatchListInterface
{
    use HasFactory;

    /**
     * @var string
     */
    protected $primaryKey = 'location_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'universe_locations';

    /** @return MorphTo<Model, $this> */
    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'location_id', 'location_id');
    }

    /**
     * Every asset that ultimately sits in this location, at any nesting depth (flattened via the
     * denormalized root_location_id). Lets "location contains a matching asset at any depth" be a
     * single flat whereHas instead of the recursive assets/content/content.content traversal.
     *
     * @return HasMany<Asset, $this>
     */
    public function descendantAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'root_location_id', 'location_id');
    }

    /**
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    #[Scope]
    protected function filterByRegionIds(Builder $query, int|array $regions): Builder
    {
        $regionIds = is_array($regions) ? $regions : [$regions];

        return $query->whereHas('locatable.system.constellation', function (Builder $query) use ($regionIds) {
            $query->whereIn('region_id', $regionIds);
        });
    }

    /**
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    #[Scope]
    protected function filterBySystemIds(Builder $query, int|array $systems): Builder
    {
        $systemIds = is_array($systems) ? $systems : [$systems];

        return $query->whereHas('locatable.system', function (Builder $query) use ($systemIds) {
            $query->whereIn('system_id', $systemIds);
        });
    }
}
