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
use Seatplus\Eveapi\database\factories\ContractFactory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Corporation\CorporationInfo;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Traits\HasWatchlist;

class Contract extends Model
{
    use HasFactory;
    use HasWatchlist;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
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

    public function items()
    {
        return $this->hasMany(ContractItem::class, 'contract_id', 'contract_id');
    }

    public function start_location()
    {
        return $this->hasOne(Location::class, 'location_id', 'start_location_id');
    }

    public function end_location()
    {
        return $this->hasOne(Location::class, 'location_id', 'end_location_id');
    }

    public function assignee_character()
    {
        return $this->belongsTo(CharacterInfo::class, 'assignee_id', 'character_id');
    }

    public function assignee_corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'assignee_id', 'corporation_id');
    }

    public function issuer_character()
    {
        return $this->belongsTo(CharacterInfo::class, 'issuer_id', 'character_id');
    }

    public function issuer_corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'issuer_corporation_id', 'corporation_id');
    }

    public function characters()
    {
        return $this->morphedByMany(CharacterInfo::class, 'contractable', null, 'contract_id');
    }

    public function scopeInRegion(Builder $query, int | array $regions): Builder
    {
        $region_ids = is_array($regions) ? $regions : [$regions];

        return $query
            ->whereHas('start_location.locatable', fn (Builder $query) => $query->whereHas('system.region', fn ($query) => $query->whereIn('universe_regions.region_id', $region_ids)))
            ->orWhereHas('end_location.locatable', fn (Builder $query) => $query->whereHas('system.region', fn ($query) => $query->whereIn('universe_regions.region_id', $region_ids)));
    }

    public function scopeInSystems(Builder $query, int | array $systems): Builder
    {
        $system_ids = is_array($systems) ? $systems : [$systems];

        return $query
            ->whereHas('start_location.locatable', fn (Builder $query) => $query->whereHas('system', fn ($query) => $query->whereIn('system_id', $system_ids)))
            ->orWhereHas('end_location.locatable', fn (Builder $query) => $query->whereHas('system', fn ($query) => $query->whereIn('system_id', $system_ids)));
    }

    public function scopeOfTypes(Builder $query, int | array $types) : Builder
    {
        $type_ids = is_array($types) ? $types : [$types];

        return $query->whereHas('items.type', fn (Builder $query) => $query->whereIn('type_id', $type_ids));
    }

    public function scopeOfGroups(Builder $query, int | array $groups) : Builder
    {
        $group_ids = is_array($groups) ? $groups : [$groups];

        return $query->whereHas('items.type', fn (Builder $query) => $query->whereIn('group_id', $group_ids));
    }

    public function scopeOfCategories(Builder $query, int | array $categories) : Builder
    {
        $category_ids = is_array($categories) ? $categories : [$categories];

        return $query->whereHas('items.type.group', fn (Builder $query) => $query->whereIn('category_id', $category_ids));
    }
}
