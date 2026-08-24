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

namespace Seatplus\Eveapi\Models\Contracts;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

#[Unguarded] #[WithoutIncrementing]

class Contract extends Model implements LocationWatchListInterface, TypeWatchListInterface
{
    use HasFactory;

    /**
     * @var string
     */
    protected $primaryKey = 'contract_id';

    /**
     * @return Attribute<CorporationInfo|CharacterInfo, never>
     */
    public function issuer(): Attribute
    {
        return new Attribute(fn () => $this->for_corporation ? $this->issuerCorporation : $this->issuerCharacter);
    }

    /**
     * @return Attribute<CorporationInfo|CharacterInfo, never>
     */
    public function assignee(): Attribute
    {
        return new Attribute(get: fn () => $this->assigneeCharacter ?? $this->assigneeCorporation);
    }

    /** @return HasMany<ContractItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id', 'contract_id');
    }

    /** @return HasOne<Location, $this> */
    public function startLocation(): HasOne
    {
        return $this->hasOne(Location::class, 'location_id', 'start_location_id');
    }

    /** @return HasOne<Location, $this> */
    public function endLocation(): HasOne
    {
        return $this->hasOne(Location::class, 'location_id', 'end_location_id');
    }

    /** @return BelongsTo<CharacterInfo, $this> */
    public function assigneeCharacter(): BelongsTo
    {
        return $this->belongsTo(CharacterInfo::class, 'assignee_id', 'character_id');
    }

    /** @return BelongsTo<CorporationInfo, $this> */
    public function assigneeCorporation(): BelongsTo
    {
        return $this->belongsTo(CorporationInfo::class, 'assignee_id', 'corporation_id');
    }

    /** @return BelongsTo<CharacterInfo, $this> */
    public function issuerCharacter(): BelongsTo
    {
        return $this->belongsTo(CharacterInfo::class, 'issuer_id', 'character_id');
    }

    /** @return BelongsTo<CorporationInfo, $this> */
    public function issuerCorporation(): BelongsTo
    {
        return $this->belongsTo(CorporationInfo::class, 'issuer_corporation_id', 'corporation_id');
    }

    /** @return MorphToMany<CharacterInfo, $this> */
    public function characters(): MorphToMany
    {
        return $this->morphedByMany(CharacterInfo::class, 'contractable', null, 'contract_id');
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    #[Scope]
    protected function filterByRegionIds(Builder $query, int|array $regions): Builder
    {
        $regionIds = is_array($regions) ? $regions : [$regions];

        return $query // TODO merge tables like assets for improved db performance
            ->whereHas('startLocation.locatable.system.region', fn (Builder $query) => $query->whereIn('universe_regions.region_id', $regionIds))
            ->orWhereHas('endLocation.locatable.system.region', fn (Builder $query) => $query->whereIn('universe_regions.region_id', $regionIds));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    #[Scope]
    protected function filterBySystemIds(Builder $query, int|array $systems): Builder
    {
        $systemIds = is_array($systems) ? $systems : [$systems];

        return $query
            ->whereHas('startLocation.locatable.system', fn (Builder $query) => $query->whereIn('system_id', $systemIds))
            ->orWhereHas('endLocation.locatable.system', fn (Builder $query) => $query->whereIn('system_id', $systemIds));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    #[Scope]
    protected function filterByTypeIds(Builder $query, int|array $types): Builder
    {
        $typeIds = is_array($types) ? $types : [$types];

        return $query->whereHas('items', fn (Builder $query) => $query->whereIn('type_id', $typeIds));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    #[Scope]
    protected function filterByGroupIds(Builder $query, int|array $groups): Builder
    {
        $groupIds = is_array($groups) ? $groups : [$groups];

        return $query->whereHas('items.type', fn (Builder $query) => $query->whereIn('group_id', $groupIds));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    #[Scope]
    protected function filterByCategoryIds(Builder $query, int|array $category): Builder
    {
        $categoryIds = is_array($category) ? $category : [$category];

        return $query->whereHas('items.type.group', fn (Builder $query) => $query->whereIn('category_id', $categoryIds));
    }
}
