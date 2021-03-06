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

class Asset extends Model
{
    use HasFactory;

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

        return $query->whereHas('location', fn (Builder $query) => $query
            ->whereHasMorph(
                'locatable',
                '*',
                fn (Builder $query) => $query
                    ->whereHas('system.region', fn (Builder $query) => $query
                        ->whereIn('universe_regions.region_id', $region_ids)
                    )
                )
        );
    }

    public function scopeInSystems(Builder $query, int | array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        return $query->whereHas('location', fn (Builder $query) => $query
            ->whereHasMorph(
                'locatable',
                '*',
                fn (Builder $query) => $query
                    ->whereHas('system', fn (Builder $query) => $query
                        ->whereIn('universe_systems.system_id', $system_ids)
                    )
            )
        );
    }

    public function scopeSearch(Builder $query, string $query_string): Builder
    {
        /*return $query->where('character_assets.name','like', '%' . $query_string . '%');*/
        return $query->where(function ($query) use ($query_string) {
            $query->where('name', 'like', '%' . $query_string . '%')
                ->orWhereHas('type', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                ->orWhereHas('type.group', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                // Content
                ->orWhereHas('content', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                ->orWhereHas('content.type', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                ->orWhereHas('content.type.group', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                // Content Content
                ->orWhereHas('content.content', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                ->orWhereHas('content.content.type', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                })
                ->orWhereHas('content.content.type.group', function (Builder $query) use ($query_string) {
                    $query->where('name', 'like', '%' . $query_string . '%');
                });
        });
    }
}
