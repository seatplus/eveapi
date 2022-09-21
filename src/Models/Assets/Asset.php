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
use Seatplus\Eveapi\database\factories\AssetFactory;
use Seatplus\Eveapi\Events\AssetUpdating;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Station;
use Seatplus\Eveapi\Models\Universe\Structure;
use Seatplus\Eveapi\Models\Universe\System;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Traits\HasWatchlist;

class Asset extends Model
{
    use HasFactory;
    use HasWatchlist;

    const ASSET_SAFETY = 2004;

    protected array $affiliated_ids = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'assetable_id' => 'integer',
        'type_id' => 'integer',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'updating' => AssetUpdating::class,
    ];

    protected static function newFactory()
    {
        return AssetFactory::new();
    }

    public function assetable()
    {
        return $this->morphTo();
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function container()
    {
        return $this->belongsTo(self::class, 'location_id', 'item_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function content()
    {
        return $this->hasMany(self::class, 'location_id', 'item_id');
    }

    public function location()
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

    public function scopeInRegion(Builder $query, int | array $regions): Builder
    {
        $region_ids = is_array($regions) ? $regions : [$regions];

        $query->with('location.locatable.system.region');

        return $query->whereRelation(
            'location',
            fn (Builder $query) => $query
                ->whereMorphRelation(
                    'locatable',
                    [Station::class, Structure::class],
                    function (Builder $query) use ($region_ids) {

                        $query->whereRelation('system.region', fn (Builder $query) => $query->whereIn('universe_regions.region_id', $region_ids));
                    }
                )
        );
    }

    public function scopeInSystems(Builder $query, int | array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        $query->with('location.locatable.system');

        return $query->whereRelation(
            'location',
            fn (Builder $query) => $query
            ->whereHasMorph(
                'locatable',
                [Station::class, Structure::class],
                function (Builder $query, $type) use ($system_ids) {

                    $column = $type === Station::class ? 'universe_stations.system_id' : 'universe_structures.solar_system_id';

                    $query->whereIn($column, $system_ids);
                }
            )
        );

    }

    public function scopeOfTypes(Builder $query, int | array $types) : Builder
    {
        $type_ids = is_array($types) ? $types : [$types];

        $query->with(['type', 'content.type', 'content.content.type']);

        return $query
            ->whereRelation('type', fn (Builder $query) => $query->whereIn('type_id', $type_ids))
            ->orWhereRelation(
                'content',
                fn (Builder $query) => $query
                    ->whereRelation('type', fn (Builder $query) => $query->whereIn('type_id', $type_ids))
                    // content.content
                    ->orWhereRelation(
                        'content',
                        fn (Builder $query) => $query
                            ->whereRelation('type', fn (Builder $query) => $query->whereIn('type_id', $type_ids))
                    )
            );
    }

    public function scopeOfGroups(Builder $query, int | array $groups) : Builder
    {
        $group_ids = is_array($groups) ? $groups : [$groups];

        $query->with(['type.group', 'content.type.group', 'content.content.type.group']);

        return $query
            ->whereRelation('type.group', fn (Builder $query) => $query->whereIn('group_id', $group_ids))
            ->orWhereRelation(
                'content',
                fn (Builder $query) => $query
                    ->whereRelation('type.group', fn (Builder $query) => $query->whereIn('group_id', $group_ids))
                    // content.content
                    ->orWhereRelation(
                        'content',
                        fn (Builder $query) => $query
                            ->whereRelation('type.group', fn (Builder $query) => $query->whereIn('group_id', $group_ids))
                    )
            );
    }

    public function scopeOfCategories(Builder $query, int | array $categories) : Builder
    {
        $category_ids = is_array($categories) ? $categories : [$categories];

        $query->with(['type.group', 'content.type.group', 'content.content.type.group']);

        return $query
            ->whereRelation('type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids))
            // content
            ->orWhereRelation(
                'content',
                fn (Builder $query) => $query
                    ->whereRelation('type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids))
                    // content.content
                    ->orWhereRelation(
                        'content',
                        fn (Builder $query) => $query
                            ->whereRelation('type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids))
                    )
            );
    }

    public function scopeSearch(Builder $query, string $terms = null)
    {
        $query->with(['type.group', 'content.type.group', 'content.content.type.group']);

        collect(str_getcsv($terms, ' ', '"'))->filter()
            ->each(function ($term) use ($query) {
                $term = $term.'%';

                $query
                    ->where('name_normalized', 'like', $term)
                    ->orWhereRelation('type', 'name_normalized', 'like', $term)
                    ->orWhereRelation('type.group', 'name_normalized', 'like', $term)
                    ->orWhereHas(
                        'content',
                        fn ($query) => $query
                            ->where('name_normalized', 'like', $term)
                            ->orWhereRelation('type', 'name_normalized', 'like', $term)
                            ->orWhereRelation('type.group', 'name_normalized', 'like', $term)
                            ->orWhereHas(
                                'content',
                                fn ($query) => $query
                                    ->where('name_normalized', 'like', $term)
                                    ->orWhereRelation('type', 'name_normalized', 'like', $term)
                                    ->orWhereRelation('type.group', 'name_normalized', 'like', $term)
                            )
                    );
            });
    }
}
