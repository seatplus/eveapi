<?php

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

namespace Seatplus\Eveapi\Models\Assets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Traits\HasWatchlist;

class Asset extends Model
{
    use HasFactory;
    use HasWatchlist;

    const ASSET_SAFETY = 2004;

    protected array $affiliated_ids = [];

    protected $guarded = [];

    /**
     * @var string
     */
    protected $primaryKey = 'item_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $casts = [
        'assetable_id' => 'integer',
        'type_id' => 'integer',
    ];

    public function assetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function type(): HasOne
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(self::class, 'location_id', 'item_id');
    }

    public function content(): HasMany
    {
        return $this->hasMany(self::class, 'location_id', 'item_id');
    }

    public function location(): HasOne
    {
        //Todo create morphTo relation
        return $this->hasOne(Location::class, 'location_id', 'location_id');
    }

    public function scopeAssetsLocationIds(Builder $query): Builder
    {
        return $query->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
            ->addSelect('location_id');
    }

    public function scopeWithoutAssetSafety(Builder $query): Builder
    {
        return $query->where('location_id', '<>', self::ASSET_SAFETY);
    }

    public function scopeInRegion(Builder $query, int|array $regions): Builder
    {
        $region_ids = is_array($regions) ? $regions : [$regions];

        return $query->whereIn('region_id', $region_ids);
    }

    public function scopeInSystems(Builder $query, int|array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        return $query->whereIn('solar_system_id', $system_ids);
    }

    public function scopeOfTypes(Builder $query, int|array $types): Builder
    {
        $type_ids = is_array($types) ? $types : [$types];

        return $query->whereIn('type_id', $type_ids);
    }

    public function scopeOfGroups(Builder $query, int|array $groups): Builder
    {
        $group_ids = is_array($groups) ? $groups : [$groups];

        return $query->whereIn('group_id', $group_ids);
    }

    public function scopeOfCategories(Builder $query, int|array $categories): Builder
    {
        $category_ids = is_array($categories) ? $categories : [$categories];

        return $query->whereIn('category_id', $category_ids);
    }

    public function scopeSearch(Builder $query, ?string $terms = null): void
    {
        collect(str_getcsv($terms, ' ', '"'))->filter()
            ->each(function ($term) use ($query) {
                $term = $term.'%';

                $query->where('name_normalized', 'like', $term)
                    ->orWhere('type_name_normalized', 'like', $term)
                    ->orWhere('group_name_normalized', 'like', $term)
                    ->orWhere('category_name_normalized', 'like', $term);
            });
    }

    public function scopeWithRecursiveContent(Builder $query): Builder
    {
        $sub_query = $query
            ->unionAll(
                $this->newQuery()->select('assets.*')->join('tree', 'tree.location_id', '=', 'assets.item_id')
            );

        return $this->newQuery() // @phpstan-ignore-line
            ->from('tree')
            ->withRecursiveExpression('tree', $sub_query);
    }
}
