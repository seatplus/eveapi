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

namespace Seatplus\Eveapi\Models\Contacts;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

#[Unguarded]
class Contact extends Model
{
    use HasFactory;

    /** @return MorphTo<Model, $this> */
    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<ContactLabel, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(ContactLabel::class);
    }

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    #[Scope]
    protected function entityFilter(Builder $query, array $contactableIds): Builder
    {
        return $query->whereIn('contactable_id', $contactableIds);
    }

    /** @return Attribute<?CharacterAffiliation, never> */
    protected function affiliation(): Attribute
    {
        return Attribute::make(get: function () {
            $this->loadMissing([
                'characterAffiliation',
                'corporationAffiliation',
                'allianceAffiliation',
                'factionAffiliation',
            ]);

            return collect([
                $this->characterAffiliation,
                $this->corporationAffiliation,
                $this->allianceAffiliation,
                $this->factionAffiliation,
            ])
                ->filter()
                ->first();
        });
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function characterAffiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'character_id', 'contact_id');
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function corporationAffiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'corporation_id', 'contact_id');
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function allianceAffiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'alliance_id', 'contact_id');
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function factionAffiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'faction_id', 'contact_id');
    }
}
