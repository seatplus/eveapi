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
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Traits\HasWatchlist;

class Asset extends Model
{
    use HasFactory, HasWatchlist;

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

    public function scopeAffiliated(Builder $query, array $affiliated_ids, ?array $character_ids = null): Builder
    {
        return $query->when($character_ids, function ($query, $character_ids) use ($affiliated_ids) {
            return $query->entityFilter(collect($character_ids)->map(fn ($character_id) => intval($character_id))->intersect($affiliated_ids)->toArray());
        }, function ($query) {
            return $query->entityFilter(auth()->user()->characters->pluck('character_id')->toArray());
        });
    }

    public function scopeWithoutAssetSafety(Builder $query): Builder
    {
        return $query->where('location_id', '<>', self::ASSET_SAFETY);
    }

    public function scopeEntityFilter(Builder $query, array $character_ids): Builder
    {
        return $query->whereIn('assetable_id', $character_ids);
    }

    public function scopeInRegion(Builder $query, int | array $regions): Builder
    {
        $region_ids = is_array($regions) ? $regions : [$regions];

        return $query->whereHas(
            'location.locatable',
            fn (Builder $query) => $query
            ->whereHas(
                'system.region',
                fn ($query) => $query
                ->whereIn('universe_regions.region_id', $region_ids)
            )
        );
    }

    public function scopeInSystems(Builder $query, int | array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        return $query->whereHas(
            'location.locatable',
            fn (Builder $query) => $query
            ->whereHas(
                'system',
                fn ($query) => $query
                ->whereIn('system_id', $system_ids)
            )
        );
    }

    public function scopeOfTypes(Builder $query, int | array $types) : Builder
    {
        $type_ids = is_array($types) ? $types : [$types];

        return $query->whereHas('type', fn (Builder $query) => $query->whereIn('type_id', $type_ids))
            ->orWhereHas('content.type', fn (Builder $query) => $query->whereIn('type_id', $type_ids))
            ->orWhereHas('content.content.type', fn (Builder $query) => $query->whereIn('type_id', $type_ids));
    }

    public function scopeOfGroups(Builder $query, int | array $groups) : Builder
    {
        $group_ids = is_array($groups) ? $groups : [$groups];

        return $query->whereHas('type', fn (Builder $query) => $query->whereIn('group_id', $group_ids))
            ->orWhereHas('content.type', fn (Builder $query) => $query->whereIn('group_id', $group_ids))
            ->orWhereHas('content.content.type', fn (Builder $query) => $query->whereIn('group_id', $group_ids));
    }

    public function scopeOfCategories(Builder $query, int | array $categories) : Builder
    {
        $category_ids = is_array($categories) ? $categories : [$categories];

        return $query->whereHas('type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids))
            ->orWhereHas('content.type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids))
            ->orWhereHas('content.content.type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids));
    }

    public function scopeSearch(Builder $query, string $terms = null)
    {
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
