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

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function labels(): HasMany
    {
        return $this->hasMany(ContactLabel::class);
    }

    #[Scope]
    protected function entityFilter(Builder $query, array $contactable_ids): Builder
    {
        return $query->whereIn('contactable_id', $contactable_ids);
    }

    /** @return Attribute<?CharacterAffiliation, never> */
    protected function affiliation(): Attribute
    {
        return Attribute::make(get: function () {
            $this->loadMissing([
                'character_affiliation',
                'corporation_affiliation',
                'alliance_affiliation',
                'faction_affiliation',
            ]);

            return collect([
                $this->character_affiliation,
                $this->corporation_affiliation,
                $this->alliance_affiliation,
                $this->faction_affiliation,
            ])
                ->filter()
                ->first();
        });
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function character_affiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'character_id', 'contact_id');
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function corporation_affiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'corporation_id', 'contact_id');
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function alliance_affiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'alliance_id', 'contact_id');
    }

    /** @return HasOne<CharacterAffiliation, $this> */
    public function faction_affiliation(): HasOne
    {
        return $this->hasOne(CharacterAffiliation::class, 'faction_id', 'contact_id');
    }
}
