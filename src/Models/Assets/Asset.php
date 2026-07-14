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

namespace Seatplus\Eveapi\Models\Assets;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\TypeWatchListInterface;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Models\Universe\Type;

#[Unguarded] #[WithoutIncrementing]

class Asset extends Model implements TypeWatchListInterface
{
    use HasFactory;

    const int ASSET_SAFETY = 2004;

    protected array $affiliatedIds = [];

    /**
     * @var string
     */
    protected $primaryKey = 'item_id';

    public function assetable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasOne<Type, $this> */
    public function type(): HasOne
    {
        return $this->hasOne(Type::class, 'type_id', 'type_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(self::class, 'location_id', 'item_id');
    }

    public function rootItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_item_id', 'item_id');
    }

    public function content(): HasMany
    {
        return $this->hasMany(self::class, 'location_id', 'item_id');
    }

    public function location(): HasOne
    {
        // Todo create morphTo relation
        return $this->hasOne(Location::class, 'location_id', 'location_id');
    }

    #[Scope]
    protected function assetsLocationIds(Builder $query): Builder
    {
        return $query->whereIn('location_flag', ['Hangar', 'AssetSafety', 'Deliveries'])
            ->addSelect('location_id');
    }

    #[Scope]
    protected function withoutAssetSafety(Builder $query): Builder
    {
        return $query->where('location_id', '<>', self::ASSET_SAFETY);
    }

    #[\Override]
    #[Scope]
    public function filterByTypeIds(Builder $query, int|array $types): Builder
    {
        $typeIds = is_array($types) ? $types : [$types];

        return $query->whereIn('type_id', $typeIds);
    }

    #[\Override]
    #[Scope]
    public function filterByGroupIds(Builder $query, int|array $groups): Builder
    {
        $groupIds = is_array($groups) ? $groups : [$groups];

        return $query->whereIn('group_id', $groupIds);
    }

    #[\Override]
    #[Scope]
    public function filterByCategoryIds(Builder $query, int|array $categories): Builder
    {
        $categoryIds = is_array($categories) ? $categories : [$categories];

        return $query->whereIn('category_id', $categoryIds);
    }

    /**
     * Assets whose type->group->category chain is resolvable but whose
     * denormalized universe columns are not fully populated yet.
     *
     * This is the work set of EnrichAssetTypeGroupCategoryJob. It keys off every
     * column the job fills - not just group_id - so a row left partially filled
     * (e.g. group_id set by an older code path but the *_name_normalized columns
     * still null) is healed too. Loop-safe: the source names are stored generated
     * columns (non-null once the entity exists), so a filled row never re-matches.
     */
    public function scopeNeedsUniverseEnrichment(Builder $query): Builder
    {
        return $query
            ->has('type.group.category')
            ->where(fn (Builder $query) => $query
                ->whereNull('group_id')
                ->orWhereNull('category_id')
                ->orWhereNull('type_name_normalized')
                ->orWhereNull('group_name_normalized')
                ->orWhereNull('category_name_normalized'));
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'assetable_id' => 'integer',
            'type_id' => 'integer',
            'root_location_id' => 'integer',
            'root_item_id' => 'integer',
        ];
    }
}
