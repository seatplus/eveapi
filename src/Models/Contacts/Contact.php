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

namespace Seatplus\Eveapi\Models\Contacts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Seatplus\Eveapi\database\factories\ContactFactory;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

class Contact extends Model
{
    protected $guarded = false;

    use HasFactory;

    protected static function newFactory()
    {
        return ContactFactory::new();
    }

    public function contactable()
    {
        return $this->morphTo();
    }

    public function labels(): HasMany
    {
        return $this->hasMany(ContactLabel::class);
    }

    public function affiliations()
    {
        if ($this->contact_type === 'character') {
            return $this->belongsTo(CharacterAffiliation::class, 'contact_id', 'character_id');
        }

        if ($this->contact_type === 'corporation') {
            return $this->belongsTo(CharacterAffiliation::class, 'contact_id', 'corporation_id');
        }

        return $this->belongsTo(CharacterAffiliation::class, 'contact_id', 'alliance_id');
    }

    public function scopeAffiliated(Builder $query, array $affiliated_ids, ?array $contactable_ids = null): Builder
    {
        return $query->when($contactable_ids, function ($query, $contactable_ids) use ($affiliated_ids) {
            return $query->entityFilter(collect($contactable_ids)->map(fn ($character_id) => intval($character_id))->intersect($affiliated_ids)->toArray());
        }, function ($query) {
            return $query->entityFilter(auth()->user()->characters->pluck('character_id')->toArray());
        });
    }

    public function scopeEntityFilter(Builder $query, array $contactable_ids): Builder
    {
        return $query->whereIn('contactable_id', $contactable_ids);
    }
}
