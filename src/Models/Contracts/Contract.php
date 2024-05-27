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

namespace Seatplus\Eveapi\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\LocationWatchListInterface;
use Seatplus\Eveapi\Models\TypeWatchListInterface;
use Seatplus\Eveapi\Models\Universe\Location;

class Contract extends Model implements LocationWatchListInterface, TypeWatchListInterface
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @var string
     */
    protected $primaryKey = 'contract_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public function getIssuerAttribute()
    {
        return $this->for_corporation ? $this->issuer_corporation : $this->issuer_character;
    }

    public function getAsigneeAttribute()
    {
        return $this->assignee_character ?? $this->assignee_corporation;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id', 'contract_id');
    }

    public function start_location(): HasOne
    {
        return $this->hasOne(Location::class, 'location_id', 'start_location_id');
    }

    public function end_location(): HasOne
    {
        return $this->hasOne(Location::class, 'location_id', 'end_location_id');
    }

    public function assignee_character(): BelongsTo
    {
        return $this->belongsTo(CharacterInfo::class, 'assignee_id', 'character_id');
    }

    public function assignee_corporation(): BelongsTo
    {
        return $this->belongsTo(CorporationInfo::class, 'assignee_id', 'corporation_id');
    }

    public function issuer_character(): BelongsTo
    {
        return $this->belongsTo(CharacterInfo::class, 'issuer_id', 'character_id');
    }

    public function issuer_corporation(): BelongsTo
    {
        return $this->belongsTo(CorporationInfo::class, 'issuer_corporation_id', 'corporation_id');
    }

    public function characters(): MorphToMany
    {
        return $this->morphedByMany(CharacterInfo::class, 'contractable', null, 'contract_id');
    }

    public function scopeFilterByRegionIds(Builder $query, int|array $regions): Builder
    {
        $region_ids = is_array($regions) ? $regions : [$regions];

        return $query // TODO merge tables like assets for improved db performance
            ->whereHas('start_location.locatable.system.region', fn (Builder $query) => $query->whereIn('universe_regions.region_id', $region_ids))
            ->orWhereHas('end_location.locatable.system.region', fn (Builder $query) => $query->whereIn('universe_regions.region_id', $region_ids));
    }

    public function scopeFilterBySystemIds(Builder $query, int|array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        return $query
            ->whereHas('start_location.locatable.system', fn (Builder $query) => $query->whereIn('system_id', $system_ids))
            ->orWhereHas('end_location.locatable.system', fn (Builder $query) => $query->whereIn('system_id', $system_ids));
    }

    public function scopeFilterByTypeIds(Builder $query, int|array $types): Builder
    {
        $type_ids = is_array($types) ? $types : [$types];

        return $query->whereHas('items', fn (Builder $query) => $query->whereIn('type_id', $type_ids));
    }

    public function scopeFilterByGroupIds(Builder $query, int|array $groups): Builder
    {
        $group_ids = is_array($groups) ? $groups : [$groups];

        return $query->whereHas('items.type', fn (Builder $query) => $query->whereIn('group_id', $group_ids));
    }

    public function scopeFilterByCategoryIds(Builder $query, int|array $category): Builder
    {
        $category_ids = is_array($category) ? $category : [$category];

        return $query->whereHas('items.type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids));
    }
}
